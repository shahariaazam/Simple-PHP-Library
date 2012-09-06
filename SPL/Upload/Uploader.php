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
 * @version 1.8
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
     * Property that determins if any files have been set for upload
     *
     * @var boolean
     */
    private $set_files = false;

    /**
     * File list
     *
     * @var array
     */
    private $file_list = array();

    /**
     * Array with files data
     *
     * @var array
     */
    private $files = array();

    /**
     * Error codes
     *
     * @var array
     */
    private $errors;

    /**
     * Sets the class options
     *
     * @param array $options
     * @return void
     */
    public function __construct(array $options = array())
    {
        // ==== Initializing default values ==== //
        $this->log    = '';
        $this->errors = array();

        // ==== Default $options ==== //
        $this->options['debug']          = false;
        $this->options['mail']           = '';
        $this->options['uploads_dir']    = 'uploads/';
        $this->options['autocreate_dir'] = true;
        $this->options['simulate']       = false;
        $this->options['extension']      = 'keys'; // Available values: keys, values
        $this->options['extensions']     = array(
            //Office
            "doc"  => "Microsoft Word 2003 Document",
            "docx" => "Microsoft Word 2007 Document",
            "xls"  => "Microsoft Excel 2003 Workbook",
            "xlsx" => "Microsoft Excel 2007 Workbook",
            // Database
            "db"  => "Database File",
            "dbf" => "FoxPro Database File",
            // text
            "txt" => "Text File",
            "rtf" => "Rich Text Format",
            "ini" => "Ini file",
            // C++
            "cpps" => "C++ Source File",
            "cpph" => "C++ Header File",
            // Java
            "javas" => "Java Source File",
            "javac" => "Java Class File",
            // Pascal
            "pas" => "Pascal File",
            // images
            "gif" => "GIF Picture",
            "jpg" => "JPG Picture",
            "bmp" => "BMP Picture",
            "png" => "PNG Picture",
            // compressed
            "zip"   => "ZIP Archive",
            "tar"   => "TAR Archive",
            "gzip"  => "GZIP Archive",
            "bzip2" => "BZIP2 Archive",
            "rar"   => "RAR Archive",
            // music
            "mp3"  => "MP3 Audio File",
            "wav"  => "WAV Audio File",
            "midi" => "MIDI Audio File",
            "real" => "RealAudio File",
            "mp4"  => "Music/Video File (.mp4)",
            "pls"  => ".pls Playlist",
            "m3u"  => ".m3u Playlist",
            // movie
            "mpg"   => "MPG Video File",
            "mov"   => "Movie File",
            "avi"   => "AVI Video File",
            "flash" => "Flash Movie File",
            "mkv"   => "Movie File (.mkv)",
            // Micosoft / Adobe
            "pdf" => "PDF File",
            //Disc Image
            "iso" => "Disc Image (.iso)"
        );

        // ==== Replacing the internal values with the external ones ==== //
        if(count($options) > 0)
        {
            $this->options = array_merge($this->options, $options);
        }
    }

    /**
     * Sets options
     *
     * @param array $options
     * @return void
     * @throws \SPL\Upload\Exception\InvalidArgumentException
     */
    public function setOptions($options = array())
    {
        if(is_array($options))
        {
            $this->options = array_merge($this->options, $options);
        }
        else
        {
            throw new Exception\InvalidArgumentException('The $options parameter must be an array.');
        }
    }

    /**
     * Sets the array of files to upload
     *
     * @param array $files
     * @return object
     */
    public function setFiles($files = array())
    {
        // Setting the flag
        $this->set_files = true;

        // Checking if some files were provided
        if(is_array($files) && count($files) > 0)
        {
            $this->files = $files;
        }
        // Fallback to $_FILES array
        else if(isset($_FILES) && count($_FILES) > 0)
        {
            $this->files = $_FILES;
        }

        return $this;
    }

    /**
     * Gets an entry from the options array
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $value = null;

        if(isset($this->options[$name]))
        {
            $value = $this->options[$name];
        }

        return $value;
    }

    /**
     * Returns an array of errors or false if there are none
     *
     * @param void
     * @return mixed Array of errors or false if no errors were found
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
     * Returns an array of files or false if there are none
     *
     * @param void
     * @return mixed Array of files or false if no files were found
     */
    public function getFileList()
    {
        if(count($this->file_list) == 0)
        {
            return false;
        }
        else
        {
            return $this->file_list;
        }
    }

    /**
     * Checks if the file is valid
     *
     * @param string $filename
     * @return boolean
     */
    protected static function isFileValid($filename)
    {
        if(trim($filename) == '.' || trim($filename) == '..')
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * Checks if is empty
     *
     * @param mixed $value
     * @return boolean
     */
    protected static function isEmpty($value)
    {
        $result = false;

        if(is_string($value))
        {
            $value = trim($value);

            if(empty($value))
            {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Checks if the file extension is allowed
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
            $extension = substr($filename, strrpos($filename, '.') + 1, strlen($filename));

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

            // Checking the result so we can register any errors
            if($result === false)
            {
                // ==== Adding error data ==== //
                $this->errors[$filename][] = 'The files extension ( ' . $extension . ' ) is not allowed.';
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
     * Processes the files that need to be uploaded
     *
     * @param string $index
     * @return boolean
     */
    private function process($index)
    {
        // ==== Check variable ==== //
        $isOk = true;

        // ==== The files uploaded could be array or string ==== //
        if(is_array($this->files[$index]['tmp_name'])) // Array
        {
            // ==== Going through the files ==== //
            foreach($this->files[$index]['tmp_name'] as $nr => $file)
            {
                // Checking if any errors occured
                if($isOk === true)
                {
                    // ==== Getting the filename of the original file ==== //
                    $filename = $this->files[$index]['name'][$nr];

                    // Uploading the file
                    $isOk = $this->execute($file, $filename);
                }
                else
                {
                    // Exiting the loop
                    break;
                }
            }
        }
        else // String
        {
            // ==== Getting the filename of the original file ==== //
            $filename = $this->files[$index]['name'];

            // Uploading the file
            $isOk = $this->execute($this->files[$index]['tmp_name'], $filename);
        }

        // ==== Returning result ==== //
        return $isOk;
    }

    /**
     * Uploads a file with a given filename
     *
     * @param string $source The source of the file
     * @param string $filename The name of the file. This is how it will be written on the hard drive
     * @return boolean
     */
    protected function execute($source, $filename)
    {
        // Check var
        $isOk = true;

        // Checking if we have anything to upload
        if(!self::isEmpty($filename))
        {
            // ==== Checking if the extension is allowed ==== //
            if($this->isExtensionAllowed($filename))
            {
                // Default value for moved
                $moved = true;

                // ==== Checking if the file has been uploaded successfully ==== ///
                if($this->options['simulate'] === false)
                {
                    $moved = move_uploaded_file($source, $this->options['uploads_dir'] . $filename);
                }

                // Checking if the file was succesfully moved
                if($moved === true)
                {
                    // ==== Adding the file (with path) to the files array ==== //
                    $this->file_list[] = array(
                        'filename' => $filename,
                        'filepath' => $this->options['uploads_dir'] . $filename,
                    );
                }
                else
                {
                    // ==== Adding log data ==== //
                    if($this->options['debug'])
                    {
                        $this->log .= '<b>ERROR:</b> Failed to upload file: ' . $filename . '<br /><br />';
                    }

                    // ==== Adding error data ==== //
                    $this->errors[$filename][] = 'Failed to upload file';

                    $isOk = false;
                }
            }
            else
            {
                $isOk = false;
            }
        }

        return $isOk;
    }

    /**
     * Used to upload the files
     *
     * @param mixed Array or string $indexes
     * @return boolean
     * @throws \SPL\Upload\Exception\RuntimeException
     */
    public function upload($index)
    {
        // Result
        $success = true;

        // Checking if any files have been set
        if($this->set_files === false)
        {
            $this->setFiles();
        }

        // ==== Checking if the upload directory exists == we create it if not ==== //
        if(!is_dir($this->options['uploads_dir']) && $this->options['autocreate_dir'] === true)
        {
            // Checking if we can create the directory
            if(mkdir($this->options['uploads_dir'], 0777, true) === false)
            {
                // ==== Adding log data ==== //
                if($this->options['debug'])
                {
                    $this->log .= "<strong>ERROR:</strong> The folder {$this->options['uploads_dir']} is not writable.<br /><br />";
                }

                throw new Exception\RuntimeException('The uploads directory must be writable.');
            }
        }

        // ==== The indexes variable can be either a string or array ==== //
        if(is_array($index)) // Array
        {
            // ==== Going through each index ==== //
            foreach($index as $name)
            {
                // Triggering the upload only if no errors occured
                if($success === true)
                {
                    // ==== Checking if the index is valid ==== //
                    if(isset($this->files[$name]))
                    {
                        // Getting the result of the upload
                        $result = $this->process($name);

                        // Checking the result
                        if($result === false)
                        {
                            $success = false;
                        }
                    }
                }
                else
                {
                    // Exiting
                    break;
                }
            }
        }
        else if(is_string($index)) // String
        {
            // ==== Checking if the index is valid ==== //
            if(isset($this->files[$index]))
            {
                // Getting the result of the upload
                $result = $this->process($index);

                // Checking the result
                if($result === false)
                {
                    $success = false;
                }
            }
        }
        else
        {
            $success = false;
        }

        // Checking the success status
        if($success === false)
        {
            // Rolling back the upload to avoid stray files
            $this->rollback();
        }

        // Return success status
        return $success;
    }

    /**
     * The method removes all the uploaded files
     *
     * @param void
     * @return void
     */
    public function rollback()
    {
        // Checking if the simulation is active or not
        if($this->options['simulate'] === false)
        {
            // Going through the filelist
            foreach($this->file_list as $info)
            {
                // Checking if the file exists
                if(is_file(unlink($info['filepath'])))
                {
                    // Removing the file
                    $removed = unlink($info['filepath']);

                    // Checking if the file was not successfully removed
                    if($removed === false)
                    {
                        // ==== Adding log data ==== //
                        if($this->options['debug'])
                        {
                            $this->log .= '<strong>ERROR</strong> Could not remove the file with name <em>' . $info['filename'] . '</em> from path <em>' . $info['filepath'] . '</em>.';
                        }
                    }
                }
            }
        }
    }

    /**
     * Sends debug email (if debug is active and there is something to send)
     *
     * @param void
     * @return void
     */
    public function __destruct()
    {
        // ==== Sending debug if on ==== //
        if($this->options['debug'])
        {
            // Adding more stuff to the log
            $this->log .= '<strong>' . __METHOD__ . '</strong><br /><br />';
            $this->log .= '$this->options: <pre>' . print_r($this->options, 1) . '</pre><br /><br />';
            $this->log .= '$this->file_list: <pre>' . print_r($this->file_list, 1) . '</pre><br /><br />';
            $this->log .= '$this->errors: <pre>' . print_r($this->errors, 1) . '</pre><hr><br /><br />';

            // ==== Setting up mail options ==== //
            $to      = $this->options['mail'];
            $subject = '[DEBUG] ' . __CLASS__ . ' Class ' . $_SERVER['HTTP_HOST'];
            $headers = 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/html; charset=UTF-8' . "\r\n";

            // ==== Sending debug mail ==== //
            mail($to, $subject, $this->log, $headers);
        }
    }
}