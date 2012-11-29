<?php

/**
 * Gets info about a file
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name FileInfo
 * @version 1.0
 *
 */

namespace SPL\File;

class FileInfo implements FileInterface
{
    /**
    *
    * Gets the extension of a given file
    *
    * @param string $fqpn The parameter can be a string containing the FQPN of the file or just the basename
    * @return string
    */
    public static function getExtension($fqpn)
    {
        $array = explode('.', $fqpn);
        $ext = $array[count($array) - 1];

        return $ext;
    }
}