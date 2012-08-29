<?php

/**
 * Handles downloads
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Download
 * @version 1.0
 *
 */

namespace SPL\File;

class Download
{
    /**
     * Ensures a secure download (does not reveal the filepath)
     *
     * @params string $file
     * @return void
     */
    public static function secure($file)
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