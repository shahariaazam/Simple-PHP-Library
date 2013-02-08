<?php

/**
 * Used to cut strings
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Cut
 * @version 1.0
 *
 */

namespace SPL\String;

class Cut
{
    /**
     * HTML entities
     *
     * @var array
     */
    public static $entities = array(
        "&trade;",
        "&#039;"
    );

    /**
     * Cuts a string to size similar to substr but it also checks for html entities to avoid
     * cutting a html entity in half
     *
     * @param string $string
     * @param integer $from
     * @param integer $length
     * @return string on success or false on failure
     */
   public static function keepEntities($string, $from, $length)
   {
       // ==== Getting $to limit ==== //
       $to = $from + $length;

       // ==== Going through the text and checking if there are any entities that get cut ==== //
       foreach (self::$entities as $entity)
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
           return self::keepEntities($string, $from, $length);
       }

       return false;
   }
}