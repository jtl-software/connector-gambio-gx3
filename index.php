<?php

use jtl\Connector\Application\Application;
use jtl\Connector\Gambio\Connector;

require_once __DIR__ . '/bootstrap.php';

require_once dirname(CONNECTOR_DIR) . '/GXMainComponents/Application.inc.php';

if (!strpos($_SERVER['REQUEST_URI'], 'jtlconnector/install')) {
    $connector = Connector::getInstance();
    $application = Application::getInstance();
    $application->createFeaturesFileIfNecessary(sprintf('%s/config/features.json.example', CONNECTOR_DIR));
    $application->register($connector);
    $application->run();
}
