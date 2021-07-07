<?php
require_once __DIR__ . '/bootstrap.php';

use \jtl\Connector\Application\Application;
use \jtl\Connector\Gambio\Connector;

if (!strpos($_SERVER['REQUEST_URI'], 'jtlconnector/install')) {
    /** @var Connector $connector */
    $connector = Connector::getInstance();
    /** @var Application $application */
    $application = Application::getInstance();
    $application->createFeaturesFileIfNecessary(sprintf('%s/config/features.json.example', CONNECTOR_DIR));
    $application->register($connector);
    $application->run();
}
