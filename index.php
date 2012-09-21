<?php

// Error reporting
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', true);

// Setting the include path
set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    realpath(dirname(__FILE__) . '/SPL'),
)));

// Getting the autoloader
require 'SPL/Autoload/Autoload.php';

// Namespaces
use SPL\Autoload\Autoload as Autoload;

// Creating the autoload object
Autoload::register();

/////////////////////////////////////////////////////////////////////////////////////
// AREA 53
////////////////////////////////////////////////////////////////////////////////////