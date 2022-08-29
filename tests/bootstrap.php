<?php

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '2048M');
ini_set('apc.enable_cli', 1);

date_default_timezone_set('UTC');

require_once realpath(__DIR__ . '/../vendor/autoload.php');

define('APPLICATION_PATH', realpath(__DIR__ . '/../application'));
define('TESTS_PATH', realpath(__DIR__));