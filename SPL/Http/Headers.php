<?php

/**
 *
 * The class handles headers
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Headers
 * @version 1.0
 *
 */

namespace SPL\Http;

class Headers
{
    /**
     * Returns an associative or numeric array with the headers of the provided URL
     *
     * @param string $url
     * @param boolean $assoc
     * @return array
     * @throws SPL\Http\Exception\RuntimeException
     */
    public static function getForUrl($url, $assoc = true)
    {
        // Headers array
        $headers = array();

        // Checking if the required function exists
        if(function_exists('get_headers'))
        {
            // Getting the headers
            if($assoc === true)
            {
                $headers = get_headers($url, 1);
            }
            else
            {
                $headers = get_headers($url, 1);
            }
        }
        else
        {
            // Throwing an exception
            throw new Exception\RuntimeException('I cannot seem to find the "get_headers" function. This function is required for the ' . __METHOD__ . ' method.');
        }

        // Returning the result
        return $headers;
    }

    /**
     * Replaces or adds header data
     *
     * @param array $headers
     * @param boolean $assoc
     * @return void
     */
    public static function set(array $headers, $assoc = true)
    {
        // Checking
        if($assoc === true) // Associative array
        {
            foreach ($headers as $header => $value)
            {
                header(trim($header) . ': ' . trim($value));
            }
        }
        else // Numeric array
        {
            foreach ($headers as $header)
            {
                header(trim($header));
            }
        }
    }

    /**
     * Retrieves the request headers
     *
     * @param void
     * @return array
     */
    public static function request()
    {
        // Headers
        $headers = array();

        // Going through the $_SERVER array
        foreach ($_SERVER as $key => $value)
        {
            // Checking if a specific pattern is present
            if(preg_match('/(HTTP_)/', $key))
            {
                // Adding the header info
                $headers[strtoupper($key)] = $value;
            }
        }

        // Returning the headers
        return $headers;
    }
}