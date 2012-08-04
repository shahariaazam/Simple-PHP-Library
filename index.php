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

// Getting the functions
require 'functions/common.inc.php';

// Creating the autoload object
SPL\Autoload\Autoload::registerAutoload();

/////////////////////////////////////////////////////////////////////////////////////
// AREA 53
////////////////////////////////////////////////////////////////////////////////////
new \SPL\URL\URL();