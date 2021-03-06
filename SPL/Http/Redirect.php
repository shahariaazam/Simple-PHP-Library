<?php

/**
 *
 * The class handles redirects
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Headers
 * @version 1.2
 *
 */

namespace SPL\Http;

class Redirect
{
    /**
     * Executes a redirect using the header
     *
     * @param string $url
     * @return void
     */
    public static function header($url)
    {
        // Redirecting
        header('Location: ' . $url . '');
        exit();
    }

    /**
     * Executes a redirect using the http_redirect function
     *
     * @param string $url
     * @param array $options
     * @return void
     * @throws \SPL\Http\Exception\RuntimeException
     */
    public static function http($url, $options = array())
    {
        // Throwing the exception if the required function does not exist
        if(!function_exists('http_redirect'))
        {
            throw new Exception\RuntimeException('You need the "pecl_http" PECL extension to use the Redirect::http method');
        }

        // ==== Default http_redirect parameters ==== //
        $params['params'] = array();
        $params['session'] = false;
        $params['status'] = 0;

        // ==== Merging arrays ==== //
        if(is_array($options) && count($options) > 0)
        {
            $params = array_replace($params, $options);
        }

        // Redirecting
        http_redirect($url, $params['params'], $params['session'], $params['status']);
    }
}
