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
 * @version 1.1
 */

namespace SPL\Autoload;

class Autoload
{
    /**
     * Stores the loaded classes
     * 
     * @var array
     */
    private static $loaded = array();
    
    /**
     * Stores a list of paths (usually the ones from the include path
     * 
     * @var array
     */
    private static $paths = array();
    
    /**
     * Flag that determines if the an exception is thrown or not when a class file is not found
     * 
     * @var boolean
     */
    private static $skip = false;
    
    /**
     * Sets the skip property
     * 
     * @param boolean $skip
     * @return void
     */
    public static function setSkip($skip)
    {
        // Checking if boolean
        if(is_bool($skip))
        {
            self::$skip = $skip;
        }
    }

    /**
     * Registers the autoload function
     * 
     * @param void
     * @return void
     */
    public static function registerAutoload()
    {
        // Getting the include paths
        self::$paths = explode(PATH_SEPARATOR, get_include_path());
        
        // Registering the autoload function
        $registered = spl_autoload_register(array('\SPL\Autoload\Autoload', 'load'));
        
        // Checking if the autoload class was loaded or not
        if($registered == false)
        {
            throw new \Exception('Unable to register the autoload function');
        }
    }
    
    /**
     * Can be used to set the paths manually
     * 
     * @param array $paths
     * @return void
     */
    public static function addPaths(array $paths)
    {
        // Adding the new paths
        self::$paths = array_merge(self::$paths, $paths);
    }
    
    /**
     * Loads a requested class
     * 
     * @param string $class_name
     * @return void
     */
    public static function load($class_name)
    {
        // Checking if the class was already loaded or not
        if(!isset(self::$loaded[$class_name]))
        {
            // Formating the class name
            $file = $class_name . '.php';

            // Fixing the class file name
            $file = str_replace('\\', DIRECTORY_SEPARATOR, $file);

            // Going through the directories where classes might be
            foreach(self::$paths as $path)
            {
                // Building the file path
                $file_path = $path . DIRECTORY_SEPARATOR . $file;

                // Checking if a file exists for the requested class
                if(is_file($file_path))
                {
                    // Adding the file to the loaded array
                    self::$loaded[$class_name] = $file_path;

                    // Loading the file
                    require $file_path;
                }
            }
            
            // Checking if the class file was loaded
            if(!isset(self::$loaded[$class_name]) && self::$skip === false)
            {
                throw new \SPL\Exception\SPLException('No file was found for class ' . $class_name);
            }
        }
    }
}