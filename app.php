<?php 
require_once 'vendor/autoload.php';

define('DIR', __DIR__.DIRECTORY_SEPARATOR);

$config = require_once DIR.'config.php';
$manager = new MongoDB\Driver\Manager("mongodb://{$config['mongodb']['host']}:{$config['mongodb']['port']}");

require_once DIR.'lg_login_class.php';