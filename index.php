<?php
require_once __DIR__ . '/bootstrap.php';

use \jtl\Connector\Application\Application;
use \jtl\Connector\Gambio\Gambio;

if (!strpos($_SERVER['REQUEST_URI'], 'jtlconnector/install')) {
    $connector = Gambio::getInstance();
    $application = Application::getInstance();
    $application->register($connector);
    $application->run();
}
