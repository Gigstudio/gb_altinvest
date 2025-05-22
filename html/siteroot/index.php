<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use GIG\Core\ErrorHandler;
use GIG\Core\Config;
use GIG\Core\Application;

ErrorHandler::register();

$config = new Config();
$app = new Application($config);

$app->run();
