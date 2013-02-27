<?php

/**
 * Manipulates zip archives
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Zip
 * @version 1.0
 *
 */

namespace SPL\Archive;

use ZipArchive;

class Zip
{
    /**
     * @var \ZipArchive
     */
    protected $zip;

    public function __construct()
    {
        $this->zip = new ZipArchive();
    }

    /**
     * Adds a file or a directory to a given archive
     *
     * @param $path
     * @param $archiveName
     * @return bool
     */
    public function pack($path, $archiveName)
    {
        $result = true;

        $openArch = $this->zip->open($archiveName, ZIPARCHIVE::CREATE);

        if($openArch === true)
        {
            if(is_file($path))
            {
                $result = $this->zip->addFile($path);
            }
            else if (is_dir($path))
            {
                $result = $this->packDir($path);
            }

            $result = $this->zip->close();
        }
        else
        {
            $result = false;
        }

        return $result;
    }

    /**
     * @param $dir
     * @return bool
     */
    protected function packDir($dir)
    {
        $result = true;

        if($this->zip instanceof ZipArchive)
        {
            // Adding an empty directory to the archive
            $this->zip->addEmptyDir($dir);

            // Getting all the paths from the directory
            $paths = scandir(realpath($dir));

            foreach($paths as $path)
            {
                if($path != '.' && $path != '..')
                {
                    if(is_dir($path))
                    {
                        if($this->packDir($path) == false)
                        {
                            $result = false;
                            break;
                        }
                    }
                    else if(is_file(realpath($dir . DIRECTORY_SEPARATOR . $path)))
                    {
                        if($this->zip->addFile(basename($dir) . DIRECTORY_SEPARATOR . $path) == false)
                        {
                            $result = false;
                            break;
                        }
                    }
                }
            }
        }
        else
        {
            $result = false;
        }

        return $result;
    }

    /**
     * Used to unpack a zip archive
     *
     * @param string $archive
     * @param string $directory
     * @throws \RuntimeException
     * @return mixed Returns true if the archive opened succesfully or the error code
     */
    public function unpack($archive, $directory = './')
    {
        if(!is_dir($directory))
        {
            if(!mkdir($directory))
            {
                $exceptionMessage = 'The directory did not exist';
                $exceptionMessage .= ' and attempt was made to create it, but failed.';

                throw new \RuntimeException($exceptionMessage);
            }
        }

        // ==== Check variable ==== //
        $isOk = true;

        // ==== Opening the archive ==== //
        $archOpen = $this->zip->open($archive);

        if($archOpen === true)
        {
            $this->zip->extractTo($directory);
            $this->zip->close();
        }
        else
        {
            $isOk = $archOpen;
        }

        // ==== Returning result ==== //
        return $isOk;
    }
}