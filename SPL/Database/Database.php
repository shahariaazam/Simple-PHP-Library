<?php
/**
 *
 * Enhanced CrossDatabase Engine. Default database engine is mysql
 *
 * Database object initializer
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Database
 * @version 3.6.1
 *
 * ERROR CODES:
 *
 * 2 - The database type is not supported by any implemented handlers
 *
 */

namespace SPL\Database;

use SPL\Db\DbInterface;

////////////////////////////////////////////////////////////////////////////
//  Database Initializer                                                 //
//////////////////////////////////////////////////////////////////////////
abstract class Database implements DbInterface
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
     * Called to create a database object
     *
     * @param array $options
     * @param bool $new
     * @return object on success or integer on fail: 2 for wrong options, 3 for unsupported database type
     */
    public static function init(array $options = array(), $new = false)
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
                $class = '\SPL\Database\\' . ucfirst($type);

                // ==== Creating the requested database object ==== //
                self::$instance = new $class($options);
            }
            else
            {
                $error_code = 2; // The database type is not supported by any implemented handlers
            }
        }

        // ==== Checking if there were any errors ==== //
        if($error_code === false)
        {
            return self::$instance;
        }

        return $error_code;
    }

    /**
     * Checks if the database type is supported
     *
     * @param string $type
     * @return boolean
     */
    private static function isSupported($type)
    {
        if(in_array($type, self::$supported))
        {
            return true;
        }

        return false;
    }

}
