<?php
/**
 * 
 * The image class allows you to resize images on the go or save them to the file sistem.
 * 
 * @author Brian
 * @link http://brian.hopto.org/framework_wiki/
 * @copyright 2011
 * @license Creative Commons Attribution-ShareAlike 3.0
 * 
 * @name Image
 * @version 3.0
 *
 * @uses getFileExt function from functions/common.inc.php
 * 
 */
class Image
{
    /**
     * Options array
     * 
     * @var array
     */
    private $_options;

    /**
     * An array of supported image types. The format is Array('extension' => 'alias').
     *
     * @var array
     */
    private $_supported = array('jpg' => 'jpeg', 'jpeg' => 'jpeg', 'png' => 'png');

    /**
     * Image file extension
     *
     * @var string
     */
    private $_ext;

    /**
     * Class constructor
     * 
     * @param array $options
     * @return void
     */
    public function __construct($options=array())
    {
        // ==== Default options ==== //
        $this->_options['width']     = '100';       // Width of the new image
        $this->_options['height']    = '100';       // Height of the new image
        $this->_options['mode']      = 'box';       // Can take the following values: box, fixed
        $this->_options['dir']       = 'images/';   // Directory where to put the images

        // ==== Replacing options with custom ones ==== //
        if(is_array($options))
        {
            $this->_options = array_replace($this->_options, $options);
        }
    }

    /**
     * Method used to set options after object creationg
     * 
     * @param array $options
     * @return void
     */
    public function setOpt($options=array())
    {
        // ==== Replacing the options ==== //
        if(is_array($options))
        {
            $this->_options = array_replace($this->_options, $options);
        }
    }

    /**
     * This method shows the given image but the resized version
     * 
     * @param string $image Image location
     * @param string $new_ext The type of image to output
     * @return boolean
     */
    public function show($image, $new_ext='')
    {
        // ==== Check variable ==== //
        $isOk = true;

        $this->_ext = getFileExt($image);

        // ==== Setting new image extension ===== //
        if($new_ext === '')
        {
            $new_ext = $this->_ext;
        }

        // ==== Checking if the image provided is supported
        if(key_exists($new_ext, $this->_supported))
        {
            // ==== Resizing image ==== //
            $new = $this->resizeImg($image);

            // ==== Checking if the new image is a resource ==== //
            if(is_resource($new))
            {
                // ==== Setting page header and outputting image ===== //
                header('Content-type: image/' . $this->_supported[$new_ext]);

                // ==== Creating the image ==== //
                switch($this->_supported[$new_ext])
                {
                    // == JPEG == //
                    case 'jpeg':
                    case 'jpg':
                        imagejpeg($new);
                    break;

                    // == PNG == //
                    case 'png':
                        imagepng($new);
                    break;

                    // == Do nothing == //
                    default: break;
                }
            }
            else
            {
                $isOk = false;
            }
        }
        else
        {
            $isOk = false;
        }

        // ==== Result ==== //
        return $isOk;
    }

    /**
     * The method writes the given image to the hard drive using a random name. If the name exists it retries 3 times to generate an unique one.
     *
     * @param string $image
     * @param string $new_ext
     * @return false if the operation was unsuccsessfull
     * @return false on failure or image name on success
     */
    public function write($image, $new_ext='')
    {
        // ==== Result variable ==== //
        $result = false;

        // ==== Getting the image extension ==== //
        $this->_ext = getFileExt($image);

        // ==== Setting new image extension ===== //
        if($new_ext === '')
        {
            $new_ext = $this->_ext;
        }

        // ==== Checking if the image extension is supported ==== //
        if(key_exists($new_ext, $this->_supported))
        {
            // ==== Getting the directory where the image will be stored ==== //
            $dir = $this->_options['dir'];

            // ==== Checking if the directory exists and if not we create it ==== //
            if(!is_dir($dir))
            {
                mkdir($dir);
            }

            // ==== Resizing image ==== //
            $new = $this->resizeImg($image);

            // ==== Generating random name ==== //
            $name = sha1($image . time());

            // ==== Checking if the file exists or not ==== //
            if(is_file($dir . '/' . $name . '.' . $this->_supported[$this->_ext]))
            {
                // == Retry count == //
                $retry = 3;

                // ==== Retrying ==== //
                while ($retry > 0)
                {
                    // ==== Generating random name ==== //
                    $name = sha1($image . time());

                    // ==== Checking if the file exists or not ==== //
                    if(is_file($dir . '/' . $name . '.' . $this->_supported[$this->_ext]))
                    {
                        $retry--;
                    }
                    else
                    {
                        break;
                    }
                }
            }

            // ==== Checking if we have a resource ==== //
            if(is_resource($new))
            {
                // ==== Writing image to HDD ==== //
                switch($this->_supported[$new_ext])
                {
                    // == JPEG == //
                    case 'jpeg':
                    case 'jpg':
                        imagejpeg($new, $dir.'/'.$name.'.'.$this->_supported[$new_ext], 100);
                    break;

                    // == PNG == //
                    case 'png':
                        imagepng($new, $dir.'/'.$name.'.'.$this->_supported[$new_ext], 100);
                    break;

                    // == Format not supported == //
                    default: break;
                }

                // ==== Checking if the image has been written to the hard drive ==== //
                if(is_file($dir . '/' . $name . '.' . $this->_supported[$this->_ext]))
                {
                    $result = &$name;
                }
            }
        }

        // ==== Returning result ==== //
        return $result;
    }

    /**
     * The method does the image resizing and returns the new image data.
     * 
     * @param string $image
     * @return resource or false
     */
    private function resizeImg($image)
    {
        // ==== Result variable ==== //
        $result = false;

        //////////////////////////////////////////////////////
        // CREATING THE IMAGE FROM THE FILE
        /////////////////////////////////////////////////////
        // ==== Getting picture from image ==== //
        switch($this->_supported[$this->_ext])
        {
            // == JPEG == //
            case 'jpeg':
            case 'jpg':
                $old = imagecreatefromjpeg($image);
            break;

            // == PNG == //
            case 'png':
                $old = imagecreatefrompng($image);
            break;

            // == Format not supported == //
            default:
                $old = false;
            break;
        }

        //////////////////////////////////////////////////////
        // RESIZING THE IMAGE
        /////////////////////////////////////////////////////
        // ==== Checking if the image was created succesfully from the given file ==== //
        if(is_resource($old))
        {
            // ==== Determining width, height and ratio of the old image ==== //
            $dim['width']   = imagesx($old);
            $dim['height']  = imagesy($old);
            $ratio          = $dim['width'] / $dim['height'];

            // ==== Creating new image with requested dimensions ==== //
            switch ($this->_options['mode'])
            {
                case 'box':
                    // ==== Setting new picture size && determining where to copy the picture in the new image ==== //
                    if($dim['width'] >= $dim['height'])
                    {
                        // ==== Setting new picture size ==== //
                        $width = $this->_options['width'];
                        $height = round($width / $ratio);

                        // ==== Determining the position to start copying the image over the transparent background ==== //
                        $space = $this->_options['height'] - $height;
                        $sX = 0;
                        $sY = ($space >= 0 ? round($space / 2) : 0);
                    }
                    elseif($dim['width'] < $dim['height'])
                    {
                        // ==== Setting new picture size ==== //
                        $height = $this->_options['height'];
                        $width = round($height * $ratio);

                        // ==== Determining the position to start copying the image over the transparent background ==== //
                        $space = $this->_options['width'] - $width;
                        $sX = ($space >= 0 ? round($space / 2) : 0);
                        $sY = 0;
                    }

                    // ==== Generating new image ==== //
                    $new = imagecreatetruecolor(max($width, $height), max($width, $height));
                break;

                case 'fixed':
                    // ==== Getting new image start coordinates and dimensions ==== //
                    $sX     = 0;
                    $sY     = 0;
                    $width  = $this->_options['width'];
                    $height = $this->_options['height'];

                    // ==== Generating new image ==== //
                    $new = imagecreatetruecolor($width, $height);
                break;
            }

            // ==== Checking if a new image was created ==== //
            if(is_resource($new))
            {
                // ==== Setting transparent background for new image ==== //
                imagesavealpha($new, true);
                $transparent = imagecolorallocatealpha($new, 0, 0, 0, 127);
                imagefill($new, 0, 0, $transparent);

                // ==== Copying the picture over the new image at the determined coordinates ==== //
                imagecopyresized($new, $old, $sX, $sY, 0, 0, $width, $height, $dim['width'], $dim['height']);

                // ==== Referencing the image ==== //
                $result = &$new;
            }
        }

        // ==== Returning result ==== //
        return $result;
    }
}

?>