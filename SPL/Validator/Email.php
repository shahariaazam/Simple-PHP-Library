<?php

/**
 * Validates an Email
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 * 
 * @name Email
 * @version 1.0
 * 
 */

namespace SPL\Validator;

class Email
{
    /**
     * The method validates the email address provided. It also can check the DNS to see if it is valid.
     *
     * @param string $email
     * @param boolean $checkDNS [optional]
     * @return boolean
     */
    public static function isValid($email, $checkDNS = false)
    {
        // ==== Check variable ==== //
        $isValid = true;

        // ==== Sanitizing the email ==== //
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        // ==== Validating email ==== //
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);

        // ==== Checking DNS record (if activated) if the email is ok so far ===== //
        if($email == false)
        {
            $isValid = false;
        }
        elseif($checkDNS)
        {
            // ==== Getting DNS part of the mail ==== //
            $pieces = explode('@', $email);
            $dns = $pieces[1];

            // ==== Checking if the checkdnsrr exists ==== //
            if(function_exists('checkdnsrr'))
            {
                // ==== Checking DNS ==== //
                if(checkdnsrr($dns) === false)
                {
                    $isValid = false;
                }
            }
            // ==== Checking if the gethostbyname exists ==== //
            elseif(function_exists('gethostbyname'))
            {
                // ==== Checking DNS ==== //
                if(gethostbyname($dns) === $dns)
                {
                    $isValid = false;
                }
            }
            // ==== Throwing a Warning message ==== //
            else
            {
                trigger_error('DNS checking requires either checkdnsrr or the gethostbyname function.', E_USER_WARNING);
            }
        }

        // ==== Returning result ==== //
        return $isValid;
    }
}