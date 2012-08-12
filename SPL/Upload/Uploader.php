<?php
/**
 * The class can be used to upload files to the server
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Uploader
 * @version 1.5
 * 
 */

namespace SPL\Upload;

class Uploader
{
    /**
     * Internal log of problems
     *
     * @var string
     */
    private $log;

    /**
     * Variable that contains options for the class
     *
     * @var array
     */
    private $options;

    /**
     * Mail options
     *
     * @var array
     */
    private $mopt;

    /**
     * File list
     *
     * @var array
     */
    private $files=array();

    /**
     * Error codes
     *
     * @var array
     */
    private $errors;

    /**
     * Class constructor
     *
     * @param array $options
     * @return void
     */
    public function __construct(array $options=array())
    {
        // ==== Initializing default values ==== //
        $this->log      = '';
        $this->errors   = array();

        // ==== Default $options ==== //
        $this->options['debug']         = false;
        $this->options['mail']          = 'webmaster@' . $_SERVER['HTTP_HOST'];
        $this->options['uploads_dir']   = 'uploads/';
        $this->options['extension']     = 'keys'; // Available values: keys, values
        $this->options['extensions']    = array(

            //Office
            "doc"   => "Microsoft Word 2003 Document",
            "docx"  => "Microsoft Word 2007 Document",
            "xls"   => "Microsoft Excel 2003 Workbook",
            "xlsx"  => "Microsoft Excel 2007 Workbook",

            // Database
            "db"    => "Database File",
            "dbf"   => "FoxPro Database File",

            // text
            "txt"	=> "Text File",
            "rtf"   => "Rich Text Format",
            "ini"   => "Ini file",

            // C++
            "cpps"	=> "C++ Source File",
            "cpph"	=> "C++ Header File",

            // Java
            "javas"	=> "Java Source File",
            "javac"	=> "Java Class File",

            // Pascal
            "pas"	=> "Pascal File",

            // images
            "gif"	=> "GIF Picture",
            "jpg"	=> "JPG Picture",
            "bmp"	=> "BMP Picture",
            "png"	=> "PNG Picture",

            // compressed
            "zip"	=> "ZIP Archive",
            "tar"	=> "TAR Archive",
            "gzip"	=> "GZIP Archive",
            "bzip2"	=> "BZIP2 Archive",
            "rar"	=> "RAR Archive",

            // music
            "mp3"	=> "MP3 Audio File",
            "wav"	=> "WAV Audio File",
            "midi"	=> "MIDI Audio File",
            "real"	=> "RealAudio File",
            "mp4"   => "Music/Video File (.mp4)",
            "pls"   => ".pls Playlist",
            "m3u"   => ".m3u Playlist",

            // movie
            "mpg"	=> "MPG Video File",
            "mov"	=> "Movie File",
            "avi"	=> "AVI Video File",
            "flash"	=> "Flash Movie File",
            "mkv"   => "Movie File (.mkv)",

            // Micosoft / Adobe
            "pdf"	=> "PDF File",

            //Disc Image
            "iso"   => "Disc Image (.iso)"
            
        );

        // ==== Replacing the internal values with the external ones ==== //
        if(is_array($options))
        {
            $this->options = array_merge($this->options, $options);
        }

        // ==== Setting up mail options ==== //
        $this->mopt['to']       = $this->options['mail'];
        $this->mopt['subject']  = '[DEBUG] ' . __CLASS__ . ' Class '.$_SERVER['HTTP_HOST'];
        $this->mopt['msg']      = '';
        $this->mopt['headers']  = 'MIME-Version: 1.0' . "\r\n";
        $this->mopt['headers'] .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

        // ==== Checking if the upload directory exists == we create it if not ==== //
        if(!is_dir($this->options['uploads_dir']))
        {
            mkdir($this->options['uploads_dir']);
        }
    }

    /**
     * The method checks if the file is valid (not dot and not empty
     *
     * @param string $filename
     * @return boolean
     */
    private static function isFileValid($filename)
    {
        if(trim($filename) == '.' || trim($filename) == '..' || trim($filename) == '')
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * The method is used to check if the extension is allowed
     *
     * @param string $file
     * @return boolean
     */
    private function isExtensionAllowed($file)
    {
        // ==== Result ==== //
        $result = true;

        // ==== Getting the files filename ==== //
        $filename = basename($file);

        // ==== Checking if the filename if valid ==== //
        if(self::isFileValid($filename))
        {
            // ==== Getting file extension ==== //
            $extension = substr($filename, strrpos($filename, '.')+1, strlen($filename));

            // ==== Checking if the extension is allowed ==== //
            if($this->options['extension'] == 'values') // Checking the values of the array
            {
                if(!in_array($extension, $this->options['extensions']))
                {
                    $result = false;
                }
            }
            else // Checking the keys of the array
            {
                if(array_key_exists($extension, $this->options['extensions']) == false)
                {
                    $result = false;
                }
            }
        }
        else
        {
            $result = false;
        }

        // ==== result ==== //
        return $result;
    }

    /**
     * The method does the actual uploading using the index given to it
     *
     * @param string $index
     * @return boolean
     */
    private function doUpload($index)
    {
        // ==== Check variable ==== //
        $isOk = true;

        // ==== The files uploaded could be array or string ==== //
        if(is_array($_FILES[$index]['tmp_name'])) // Array
        {
            // ==== Going through the files ==== //
            foreach($_FILES[$index]['tmp_name'] as $nr => $file)
            {
                // ==== Getting the filename of the original file ==== //
                $filename = $_FILES[$index]['name'][$nr];

                // ==== Checking if the extension is allowed ==== //
                if($this->isExtensionAllowed($filename))
                {
                    // ==== Checking if the file has been uploaded successfully ==== ///
                    if(move_uploaded_file($file, $this->options['uploads_dir'].$filename) == false)
                    {
                        // ==== Adding log data ==== //
                        if($this->options['debug'])
                        {
                            $this->log .= '<b>ERROR:</b> Failed to upload file: '.$filename.'<br /><br />';
                        }

                        // ==== Adding error data ==== //
                        $this->errors[$filename]['upload'] = true;

                        $isOk = false;
                    }
                    else
                    {
                        // ==== Adding the file (with path) to the files array ==== //
                        $this->files[] = $this->options['uploads_dir'].$filename;
                    }
                }
                else
                {
                    // ==== Adding error data ==== //
                    $this->errors[$filename]['extension'] = true;

                    $isOk = false;
                }
            }
        }
        else // String
        {
            // ==== Getting the filename of the original file ==== //
            $filename = $_FILES[$index]['name'];

            // ==== Checking if the extension is allowed ==== //
            if($this->isExtensionAllowed($filename))
            {
                // ==== Checking if the file has been uploaded successfully ==== ///
                if(move_uploaded_file($_FILES[$index]['tmp_name'], $this->options['uploads_dir'].$filename) == false)
                {
                    // ==== Adding log data ==== //
                    if($this->options['debug'])
                    {
                        $this->log .= '<b>ERROR:</b> Failed to upload file: '.$filename.'<br /><br />';
                    }

                    // ==== Adding error data ==== //
                    $this->errors[$filename]['upload'] = true;

                    $isOk = false;
                }
            }
            else
            {
                $isOk = false;
            }
        }

        // ==== Returning result ==== //
        return $isOk;
    }

    /**
     * The method is the interface with the user that does the actual selection of which files to upload
     *
     * @param mixed Array or string $indexes
     * @return mixed Array on success or false on failure
     */
    public function upload($index)
    {
        // ==== The indexes variable can be either a string or array ==== //
        if(is_array($index)) // Array
        {
            // ==== Going through each index ==== //
            foreach($index as $name)
            {
                // ==== Checking if the index is valid ==== //
                if(isset($_FILES[$name]))
                {
                    $this->doUpload($name);
                }
            }
        }
        else // String
        {
            // ==== Checking if the index is valid ==== //
            if(isset($_FILES[$index]))
            {
                $this->doUpload($index);
            }
        }

        // ===== Returning the list of files ==== //
        return $this->getFileList();
    }

    /**
     * The method returns an array of errors or false if there are none
     *
     * @param void
     * @return array or false for no errors
     */
    public function getErrors()
    {
        if(count($this->errors) == 0)
        {
            return false;
        }
        else
        {
            return $this->errors;
        }
    }

    /**
     * The method returns an array of files or false if there are none
     *
     * @param void
     * @return array or false for no errors
     */
    public function getFileList()
    {
        if(count($this->files) == 0)
        {
            return false;
        }
        else
        {
            return $this->files;
        }
    }

    /**
     * Destructor. Sends email and stuff
     *
     * @param void
     * @return void
     */
    public function __destruct()
    {
        // ==== Sending debug if on ==== //
        if($this->options['debug'] && $this->log != '')
        {
            // ==== Adding log to message ==== //
            $this->mopt['msg'] = $this->log;

            // ==== Sending debug mail ==== //
            mail($this->mopt['to'], $this->mopt['subject'], $this->mopt['msg'], $this->mopt['headers']);
        }
    }
}
