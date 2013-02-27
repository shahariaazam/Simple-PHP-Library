<?php

/**
 * Validates a given password
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 * 
 * @name Password
 * @version 1.0
 * 
 */

namespace SPL\Validator;

class Password implements ValidatorInterface
{
    /**
     * Options for the validation
     * 
     * @var array
     */
    private $options = array();

    /**
     * Sets the class options
     *
     * @param array $options
     * @return \SPL\Validator\Password
     */
    public function __construct(array $options = array())
    {
        // Some default options
        $this->options['length'] = 8;       // Minimum password length
        $this->options['number'] = true;    // Require numbers
        $this->options['lcase']  = true;    // Require lowercase letters
        $this->options['ucase']  = true;    // Require uppercase letters
        
        // ==== Replacing options with custom ones ==== //
        if(count($options) > 0)
        {
            $this->options = array_replace($this->options, $options);
        }
    }
    
    /**
     * Used to set additional options
     * 
     * @param string $name
     * @param string $value
     * @return void
     */
    public function __set($name, $value)
    {
        // Checking if the $name exists in the options array (it's useless to create a new entry if it will never be used)
        if(isset($this->options[$name]))
        {
            $this->options[$name] = $value;
        }
    }
    
    /**
     * Checks if the password is valid according to the options provided (aka it checks the complexity using the given options)
     *
     * @param string $passwd
     * @param boolean $bypass
     * @return boolean
     */
    public function isValid($passwd, $bypass = false)
    {
        // ==== Result variable ==== //
        $result = true;

        // ==== Checking if overwrite is in effect ==== //
        if($bypass === false)
        {
            // ==== Check variable ==== //
            $failed_count = 0;

            // ==== Checking if the length check is enabled ==== //
            if(isset($this->options['length'])
                && is_numeric($this->options['length'])
                && $this->options['length'] > 0
            )
            {
                // ==== Checking the length ==== //
                if(strlen(trim($passwd)) < $this->options['length'])
                {
                    $failed_count++;
                }
            }

            // ==== Checking if the number or lowercase or uppercase check is active ==== //
            if($this->options['number'] == true
                || $this->options['lcase'] == true
                || $this->options['ucase'] == true
            )
            {
                // ==== Character counters ==== //
                $lChr = 0;
                $number = 0;
                $uChr = 0;

                // ==== Checking each character in the password ==== //
                for ($i = 0; $i < strlen($passwd); $i++)
                {
                    // ==== Check variables ==== //
                    $checked = false;

                    // ==== Number check ==== //
                    if($this->options['number'] == true)
                    {
                        if(is_numeric(substr($passwd, $i, 1)))
                        {
                            $number++;

                            $checked = true;
                        }
                    }

                    // ==== Lowercase check ==== //
                    if($this->options['lcase'] == true && $checked == false)
                    {
                        if(is_string(substr($passwd, $i, 1)) && preg_match('/[a-z]/', substr($passwd, $i, 1)))
                        {
                            $lChr++;

                            $checked = true;
                        }
                    }

                    // ==== Uppercase check ==== //
                    if($this->options['ucase'] == true && $checked == false)
                    {
                        if(is_string(substr($passwd, $i, 1)) && preg_match('/[A-Z]/', substr($passwd, $i, 1)))
                        {
                            $uChr++;
                        }
                    }
                }

                // ==== Checking number count ==== //
                if($this->options['number'] == true && $number == 0)
                {
                    $failed_count++;
                }

                // ==== Checking lowercase count ==== //
                if($this->options['lcase'] == true && $lChr == 0)
                {
                    $failed_count++;
                }

                // ==== Checking uppercase count ==== //
                if($this->options['ucase'] == true && $uChr == 0)
                {
                    $failed_count++;
                }
            }

            // ==== Checking the failed count ==== //
            if($failed_count != 0)
            {
                $result = false;
            }
        }

        // ==== Returning result ==== //
        return $result;
    }    
}
