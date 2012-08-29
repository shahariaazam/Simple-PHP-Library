<?php

/**
 * Manipulates directories
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Directory
 * @version 1.0
 *
 */

namespace SPL\Directory;

class Directory
{
    /**
     * Cleans up a given directory except for the files in the whitelist
     *
     * @param string $dir
     * @param array $whitelist
     * @return void
     */
    public static function cleanup($dir, array $whitelist = array())
    {
        // ==== Reading the files from the directory and deleting the ones not present in the whitelist ==== //
        if(is_dir($dir))
        {
            // ==== Opening the directory ==== //
            $dh = opendir($dir);

            // ==== Checking if the directory was opened succesfully ==== //
            if($dh != false)
            {
                while (($file = readdir($dh)) !== false)
                {
                    // ==== Checking if the file exists in the whitelist and it's different from dot ==== //
                    if(!in_array($file, $whitelist) && $file != '.' && $file != '..')
                    {
                        // ==== Removing ==== //
                        if(is_dir($dir . $file))
                        {
                            // ==== Recursive ==== //
                            self::cleanup($dir . $file . '/', $whitelist);

                            // ==== Removing directory ==== //
                            @rmdir($dir . $file);
                        }
                        else if(is_file($dir . $file))
                        {
                            // ==== Deleting file ==== //
                            unlink($dir . $file);
                        }
                    }
                }
            }
        }
    }
}