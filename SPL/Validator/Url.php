<?php

/**
 * Validates an URL
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 * 
 * @name URL
 * @version 1.0
 * 
 */

namespace SPL\Validator;

class Url implements ValidatorInterface
{
    /**
     * Validates a given URL. It can also check if the URL is accessible (on by default).
     * 
     * @param string $url
     * @param boolean $checkIfAccessible [ optional ] When this parameter is set to true the function will make a cURL to the given URL
     * @return boolean
     */
    public static function isValid($url, $checkIfAccessible = false)
    {
        // ==== Check variable ==== //
        $isValid = false;

        // ==== Sanitizing and validating the URL ==== //
        $url = filter_var(filter_var($url, FILTER_SANITIZE_URL), FILTER_VALIDATE_URL);

        // ==== Checking if the URL passed the previous checks ==== //
        if($url !== false && (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0))
        {
            // Setting the flag to true
            $isValid = true;

            // Should we check to see if the URL also exists
            if($checkIfAccessible === true)
            {
                // ==== Initializing the cURL handle ==== //
                $ch = curl_init();

                // ==== Setting the cURL options ==== //
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

                // ==== Executing the cURL ==== //
                curl_exec($ch);

                // ==== Getting the returned code ==== //
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                // ==== Closing the cURL handle ==== //
                curl_close($ch);

                // ==== If code is different than 200 then the URL does not exist ==== //
                if($code != 200)
                {
                    $isValid = false;
                }
            }
        }

        // ==== Returning result ==== //
        return $isValid;
    }
}