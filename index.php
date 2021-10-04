<?php
use jtl\Connector\Application\Application;
use jtl\Connector\Gambio\Connector;

defined('CONNECTOR_DIR') || define("CONNECTOR_DIR", __DIR__);
defined("CONNECTOR_VERSION") || define("CONNECTOR_VERSION", file_get_contents(__DIR__.'/version'));

/** @var \Composer\Autoload\ClassLoader $loader */

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    $loader = require_once __DIR__."/vendor/autoload.php";
} else {
    $loader = require_once 'phar://' . __DIR__ . '/connector.phar/vendor/autoload.php';
}

$loader->add('', CONNECTOR_DIR . '/plugins');

require_once dirname(CONNECTOR_DIR) . '/GXMainComponents/Application.inc.php';

if (!strpos($_SERVER['REQUEST_URI'], 'jtlconnector/install')) {
    $connector = Connector::getInstance();
    $application = Application::getInstance();
    $application->register($connector);
    $application->run();
}
