<?php

/**
 * Used to print different types of varialbe
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Variable
 * @version 1.0
 *
 */

namespace SPL\Variable;

class Variable
{
    /**
     * Properly prints an array
     *
     * @param array $array
     * @return void
     */
    public static function print_array($array, $return = false)
    {
        $str = '<pre>' . print_r($array, 1) . '</pre>';

        if($return == true)
        {
            return $str;
        }

        echo $str;
    }
}