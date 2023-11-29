<?php

// Own your mark
define('EXEC_START', microtime(true));

// System time zone
date_default_timezone_set('Africa/Dar_es_Salaam');

// Composer
require "vendor/autoload.php";
include realpath(__DIR__).'/app/index.php';
