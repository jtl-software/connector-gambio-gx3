<?php
defined('CONNECTOR_DIR') || define("CONNECTOR_DIR", __DIR__);
defined("CONNECTOR_VERSION") || define("CONNECTOR_VERSION", file_get_contents(__DIR__.'/version'));

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    $loader = require_once __DIR__."/vendor/autoload.php";
} else {
    $loader = require_once 'phar://' . __DIR__ . '/connector.phar/vendor/autoload.php';
}

$loader->add('', CONNECTOR_DIR . '/plugins');