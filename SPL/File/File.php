<?php

/**
 * Identifies a file
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name File
 * @version 1.0
 *
 */

namespace SPL\File;

class File
{
    /**
     *
     * Gets the extension of a given filename
     *
     * @param string $file
     * @return string
     */
    public static function getExtension($file)
    {
        $array = explode(".", $file);
        $ext = $array[count($array) - 1];

        return $ext;
    }
}