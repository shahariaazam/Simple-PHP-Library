<?php
// Defining the class autoloader
if(!function_exists('class_loader'))
{
    function class_loader($class)
    {
        // List of loaded classes
        static $loaded = array();

        // Checking if the class was already loaded or not
        if(!isset($loaded[$class]))
        {
            // Directories where to look for the classes
            $directories = array('classes');

            // Formating the class name
            $class_filename = strtolower($class) . '.class.php';

            // Going through the directories where classes might be
            foreach($directories as $directory)
            {
                // Building the file path
                $file_path = $directory . DIRECTORY_SEPARATOR . $class_filename;

                // Checking if a file exists for the requested class
                if(is_file($file_path))
                {
                    // Adding the file to the loaded array
                    $loaded[$class] = $file_path;

                    // Loading the file
                    require $file_path;
                }
            }
        }
    }
}

// Registering the class autoloader
spl_autoload_register('class_loader');