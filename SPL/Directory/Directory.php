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
            $dir = realpath($dir) . DIRECTORY_SEPARATOR;

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
                            self::cleanup($dir . $file, $whitelist);

                            // ==== Removing directory ==== //
                            rmdir($dir . $file);
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

    /**
     * For a given directory it returns an array containing the directory tree
     *
     * @param string $dir
     * @param boolean $recursive
     * @return array
     * @throws Exception\RuntimeException
     */
    public static function getDirectoryContents($dir, $recursive = true)
    {
        // Checking if the directory exists
        if(is_dir($dir))
        {
            // Directory contents
            $contents = array();

            // Getting the files from the directory
            $files = scandir($dir);

            // Removing the "dot" files from the array
            /** @noinspection PhpUnusedParameterInspection */
            array_walk($files, function($file, $index) use (&$contents, $dir, $recursive){

                // Entry
                $entry = array();

                // Checking the file
                if(trim($file) == '.' || trim($file) == '..')
                {
                    return null;
                }

                // Checking if recursive is in effect
                if(is_dir($dir . $file . '/') && $recursive === true)
                {
                    $entry['directory'] = array(
                        'name'     => $file,
                        'path'     => $dir . $file . '/',
                        'contents' => Directory::getDirectoryContents($dir . $file . '/', $recursive)
                    );
                }
                else
                {
                    // Adding the file
                    $entry['file'] = $file;
                }

                // Adding the entry to the contents array
                $contents[$dir][] = $entry;
            });

            // Returning the contents
            return $contents;
        }

        // Throwing exception
        throw new Exception\RuntimeException('The provided directory ( ' . $dir . ' ) is invalid.');
    }
}