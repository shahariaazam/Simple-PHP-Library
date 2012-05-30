<?php
/**
 *
 * Enhanced CrossDatabase Engine. Default database engine is mysql
 *
 * Database object initializer
 *
 * @author Brian
 * @link http://brian.hopto.org/wiki/hypermvc/
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Database
 * @version 3.6
 *
 */

namespace Database;

////////////////////////////////////////////////////////////////////////////
//  Database Initializor                                                 //
//////////////////////////////////////////////////////////////////////////
abstract class Database
{

    /**
     * Supported database types
     *
     * @var array
     */
    private static $supported = array('mysql', 'pgsql', 'mysql_i', 'dbase');

    /**
     * Instance identifier
     *
     * @var object
     */
    private static $instance;

    /**
     * Singleton initiator
     *
     * @param void
     * @return object on success or integer on fail: 2 for wrong options, 3 for unsupported database type
     */
    public static function init($options=array(), $new=false)
    {
        // ==== Error code ==== //
        $error_code = false;

        // ==== Getting database type to initialize ==== //
        if(isset($options['type']))
        {
            $type = $options['type'];

            // ==== Removing the option ==== //
            unset($options['type']);
        }
        else
        {
            $type = 'mysql';
        }

        // ==== Creating database object using Singleton ==== //
        if(!isset(self::$instance) || $new == true)
        {
            // ==== Checking if the database type is supported ==== //
            if(self::isSupported($type))
            {
                // ==== Correcting the type name ==== //
                $class = '\Database\\' . ucfirst($type);

                // ==== Checking if the $options parameter is an array ==== //
                if(is_array($options))
                {
                    self::$instance = new $class($options);
                }
                else
                {
                    $error_code = 2;
                }
            }
            else
            {
                $error_code = 3;
            }
        }

        // ==== Checking if a database instance has been created ==== //
        if(is_object(self::$instance))
        {
            return self::$instance;
        }
        else
        {
            return $error_code;
        }
    }

    /**
     * The method checks if the database type is supported
     *
     * @param string type
     * @return boolean
     */
    private static function isSupported($type)
    {
        if(in_array($type, self::$supported))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

}