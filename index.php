<?php
require_once __DIR__."/vendor/autoload.php";

use \jtl\Connector\Application\Application;
use \jtl\Connector\Gambio\Gambio;

defined('CONNECTOR_DIR') || define("CONNECTOR_DIR", __DIR__);
defined("CONNECTOR_VERSION") || define("CONNECTOR_VERSION", file_get_contents(__DIR__.'/version'));


if (!strpos($_SERVER['REQUEST_URI'], 'install')) {
    $connector = Gambio::getInstance();
    $application = Application::getInstance();
    $application->register($connector);
    $application->run();
}
