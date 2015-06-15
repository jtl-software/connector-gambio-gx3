<?php
require_once __DIR__."/vendor/autoload.php";

use \jtl\Connector\Application\Application;
use \jtl\Connector\Gambio\Gambio;

defined('CONNECTOR_DIR') || define("CONNECTOR_DIR", __DIR__);
define("CONNECTOR_VERSION", "1.0.0");
define("SHOP_VERSION", "2.3.1.1");

try {
    if (!strpos($_SERVER['REQUEST_URI'], 'install')) {
        $connector = Gambio::getInstance();
        $application = Application::getInstance();
        $application->register($connector);
        $application->run();
    }
} catch (\Exception $exc) {
    $connector->exceptionHandler($exc);
}
