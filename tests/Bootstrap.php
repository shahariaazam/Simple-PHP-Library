<?php
/**
 *
 * User: brian978
 * Created on: 1/24/13
 * License: Creative Commons Attribution-ShareAlike 3.0
 *
 */

use SPL\Autoload\Autoload;

$path = realpath(dirname(__DIR__));

include $path . '/SPL/Autoload/Autoload.php';

Autoload::register(array(
                        'SPL' => $path,
                        'tests' => $path
                   ));
