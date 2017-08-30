<?php
define('APPLICATION_BASE_DIR', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
define('VENDOR_DIR', APPLICATION_BASE_DIR . DS . 'vendor');
define('COMPOSER_AUTOLOADER', VENDOR_DIR . DS . 'autoload.php');

require_once COMPOSER_AUTOLOADER;