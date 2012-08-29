<?php

/**
 * Manipulates strings
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name String
 * @version 1.0
 *
 */

namespace SPL\String;

class String
{
   /**
    * Converts a string containg a boolean value to a true boolean
    *
    * @param string $value
    * @return boolean
    */
    public static function toBoolean($value)
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
}