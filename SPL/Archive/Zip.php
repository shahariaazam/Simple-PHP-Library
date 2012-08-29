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

class Zip
{
    /**
     * Used to unpack a zip archive
     *
     * @param string $archive
     * @param string $directory
     * @return boolean
     */
    public static function unpack($archive, $directory = './')
    {
        // ==== Check variable ==== //
        $isOk = true;

        // ==== Creating the ZipArchive object ==== //
        $zip = new ZipArchive();

        // ==== Opening the archive ==== //
        if($zip->open($archive) === true)
        {
            $zip->extractTo($directory);
            $zip->close();
        }
        else
        {
            $isOk = false;
        }

        // ==== Returning result ==== //
        return $isOk;
    }
}