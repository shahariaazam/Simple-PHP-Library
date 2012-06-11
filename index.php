<?php

// Error reporting
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', true);

// Getting the autoloader
require 'autoloader.php';

// Getting the functions
require 'functions/common.inc.php';

// Used namespaces
use \Database\Database as DB;


/////////////////////////////////////////////////////////////////////////////////////
// AREA 53
////////////////////////////////////////////////////////////////////////////////////

$db = DB::init(array('type' => 'mysql_i'));
