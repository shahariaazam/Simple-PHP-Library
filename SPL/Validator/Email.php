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

class Email implements ValidatorInterface
{
    /**
     * Validates the email address provided. It can also check the DNS to see if it is valid.
     *
     * @param string $email
     * @param boolean $checkDns [ optional ]
     * @return boolean
     */
    public static function isValid($email, $checkDns = false)
    {
        // ==== Check variable ==== //
        $isValid = false;

        // ==== Sanitizing and validating the email ==== //
        $email = filter_var(filter_var($email, FILTER_SANITIZE_EMAIL), FILTER_VALIDATE_EMAIL);

        // ==== Checking DNS record (if activated) if the email is ok so far ===== //
        if($email !== false)
        {
            $isValid = true;
        }

        if($checkDns === true)
        {
            $dns = '';

            // ==== Getting DNS part of the mail ==== //
            $pieces = explode('@', $email);

            if(isset($pieces[1]))
            {
                $dns = $pieces[1];
            }

            // ==== Checking if the checkdnsrr exists ==== //
            if(function_exists('checkdnsrr') && checkdnsrr($dns) === true)
            {
                $isValid = true; var_dump($isValid);
            }
            // ==== Checking if the gethostbyname exists ==== //
            else if(function_exists('gethostbyname') && gethostbyname($dns) !== $dns)
            {
                $isValid = true; var_dump($isValid);
            }
        }

        // ==== Returning result ==== //
        return $isValid;
    }
}