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
        $array = explode('.', $file);
        $ext = $array[count($array) - 1];

        return $ext;
    }

    /**
     * Ensures a secure download (does not reveal the filepath)
     *
     * @params string $file
     * @return void
     */
    public static function download($file)
    {
        // ==== Checking if the file exists === //
        if(is_file($file))
        {
            ob_end_clean();
            ob_start();
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            @header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit();
        }
    }
}