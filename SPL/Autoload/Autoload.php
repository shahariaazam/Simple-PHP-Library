<?php

/**
 * Autoloader class
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Autoload
 * @version 2.0
 *
 */

namespace SPL\Autoload;

class Autoload
{

    /**
     * Stores the loaded classes
     *
     * @var array
     */
    protected static $loaded = array();

    /**
     * Namespaces
     *
     * @var array
     */
    protected static $namespaces = array();

    /**
     * Flag that determines if the an exception is thrown or not when a class file is not found
     *
     * @var boolean
     */
    protected static $skip = false;

    /**
     * Sets the skip property to true so that no exception is thrown when a file for a class is not found
     *
     * @param void
     * @return void
     */
    public static function skipException()
    {
        self::$skip = true;
    }

    /**
     * Registers the Autoload class as the __autoload() implementation
     *
     * @param array $namespaces
     * @return void
     */
    public static function register($namespaces = array())
    {
        // Registering the autoload function
        $registered = spl_autoload_register(array('\\' . __NAMESPACE__ . '\Autoload', 'loadClass'));

        // Checking if the autoload class was loaded or not
        if($registered === false)
        {
            throw new Exception\RuntimeException('Unable to register the autoload function');
        }

        // Registering the namespaces
        foreach($namespaces as $namespace => $path)
        {
            self::registerNamespace($namespace, $path);
        }
    }

    /**
     * Registers a given namespace
     *
     * @param string $namespace
     * @param string $path
     * @return void
     */
    protected static function registerNamespace($namespace, $path)
    {
        self::$namespaces[$namespace] = $path;
    }

    /**
     * Loads a requested class
     *
     * @param string $class
     * @return void
     * @throws Exception\RuntimeException
     */
    protected static function loadClass($class)
    {
        // Checking if the class was already loaded or not
        if(!isset(self::$loaded[$class]))
        {
            // Getting the namespace of the class
            $namespace = current(explode('\\', $class));

            // Checking if the namespace exists
            if(isset(self::$namespaces[$namespace]))
            {
                // Building the filepath
                $file = self::$namespaces[$namespace] . '/' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

                // Checking if a file exists for the requested class
                if(is_file($file))
                {
                    // Adding the file to the loaded array
                    self::$loaded[$class] = $file;

                    // Loading the file
                    include $file;
                }
            }

            // Checking if the class file was loaded
            if(!isset(self::$loaded[$class]) && self::$skip === false)
            {
                throw new Exception\RuntimeException('No file was found for class ' . $class);
            }
        }
    }

}