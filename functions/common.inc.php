<?php

/**
 * The file contains common functions
 *
 * @author brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 */

/**
 * The function redirects the user using either the header method or the http_redirect method
 *
 * @param string $url
 * @param string $method
 * @param array $settings // Optional but only for http_redirect method
 * @return void
 */
function redirect($url, $method = 'header', $settings = array())
{
    // ==== Decoding the URL ==== //
    $url = urldecode($url);

    switch($method)
    {
        default:
            header('Location: ' . $url . '');
            exit();
            break;

        case 'http_redirect':
            // ==== Default http_redirect parameters ==== //
            $params['params'] = array();
            $params['session'] = false;
            $params['status'] = 0;

            // ==== Merging arrays ==== //
            if(is_array($settings) && count($settings) > 0)
            {
                $params = array_replace($params, $settings);
            }

            if(function_exists('http_redirect'))
            {
                http_redirect($url, $params['params'], $params['session'], $params['status']);
                exit();
            }
            else
            {
                trigger_error('You need PECL extension to use the http_redirect function. <br />Please install the PECL extension or switch to header redirect.', E_USER_ERROR);
            }
            break;
    }
}

/**
 * The function cuts a string to size similar to substr but it also checks for html entities to avoid
 * cutting a html entity in half
 *
 * @param string $string
 * @param integer $from
 * @param interger $length
 * @param array $more_entities
 * @return string on success or false on failure
 */
function cutstr($string, $from, $length, $more_entities = array())
{
    // ==== HTML entities array ==== //
    $entities = array(
        "&trade;",
        "&#039;"
    );

    // ==== Adding more entities to the ones already defined ==== //
    if(is_array($more_entities) && sizeof($more_entities) > 0)
    {
        $entities = array_merge($entities, $more_entities);
    }

    // ==== Getting $to limit ==== //
    $to = $from + $length;

    // ==== Going through the text and checking if there are any entities that get cut ==== //
    foreach ($entities as $key => $entity)
    {
        // ==== Getting entity size ==== //
        $esize = strlen($entity);

        // ==== Getting start position of entity ==== //
        $epos_start = strpos($string, $entity);

        // ==== Getting end position of entity ==== //
        $epos_end = $epos_start + $esize;

        // ==== Checking if $from will cut the $entity ==== //
        if($from > $epos_start && $from <= $epos_end)
        {
            $from = $epos_start;
        }

        // ==== Checking if $to will cut the $entity ==== //
        if($to >= $epos_start && $to < $epos_end)
        {
            $to = $epos_start;
        }
    }

    // ==== Getting $current_length ==== //
    $new_length = $to - $from;

    // ==== Cutting the text to the proper length ==== //
    if($new_length <= $length)
    {
        // ==== Cutting string to size ==== //
        $string = substr($string, $from, $new_length);

        return $string;
    }
    elseif($new_length > $length) // If the text has shifted go through the function again
    {
        return cutstr($string, $from, $length, $more_entities);
    }
    else
    {
        return false;
    }
}

/**
 * The function cleans up a given directory except for the files in the whitelist
 *
 * @param string $dir
 * @param array $whitelist
 * @return void
 */
function cleanup($dir, array $whitelist = array())
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
                        cleanup($dir . $file . '/', $whitelist);

                        // ==== Removing directory ==== //
                        @rmdir($dir . $file);
                    }
                    elseif(is_file($dir . $file))
                    {
                        // ==== Deleting file ==== //
                        unlink($dir . $file);
                    }
                    else
                    {
                        // Optimization
                    }
                }
            }
        }
    }
}

/**
 * The function is used to unpack a zip archive
 *
 * @param string $archive
 * @param string $directory
 * @return boolean
 */
function unzip($archive, $directory = './')
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

/**
 * Converts a string containg a boolean value to a true boolean
 *
 * @param string $value
 * @return boolean
 */
function strtoboolean($value)
{
    if($value == 'true' || $value == '1')
    {
        $value = true;
    }
    elseif($value == 'false' || $value == '0')
    {
        $value = false;
    }

    return $value;
}

/**
 * The function ensures a secure download (does not reveal the filepath)
 *
 * @params $file
 * @return void
 */
function secure_download($file)
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
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit();
    }
}


/**
 * The function properly prints an array
 *
 * @param array $array
 * @return void
 */
function print_array($array, $return = false)
{
    $str = '<pre>' . print_r($array, 1) . '</pre>';

    if($return == true)
    {
        return $str;
    }
    else
    {
        echo $str;
    }
}