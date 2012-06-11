<?php
// Defining the class autoloader
if(!function_exists('class_loader'))
{
    function class_loader($filename)
    {
        // List of loaded classes
        static $loaded = array();

        // Checking if the class was already loaded or not
        if(!isset($loaded[$filename]))
        {
            // Directories where to look for the classes
            $directories = array('classes');

            // Formating the class name
            $file = $filename . '.php';

            // Fixing the class file name
            $file = str_replace('\\', DIRECTORY_SEPARATOR, $file);

            // Going through the directories where classes might be
            foreach($directories as $directory)
            {
                // Building the file path
                $file_path = $directory . DIRECTORY_SEPARATOR . $file;

                // Checking if a file exists for the requested class
                if(is_file($file_path))
                {
                    // Adding the file to the loaded array
                    $loaded[$filename] = $file_path;

                    // Loading the file
                    require $file_path;
                }
            }
        }
    }
}

// Registering the class autoloader
spl_autoload_register('class_loader');