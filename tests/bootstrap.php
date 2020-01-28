<?php

use jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Gambio\Gambio;
use jtl\Connector\Gambio\Mapper\PrimaryKeyMapper;
use jtl\Connector\Session\SessionHelper;

$loader = require 'vendor/autoload.php';

const  TEST_DIR = __DIR__;
const  CONNECTOR_DIR = __DIR__;

initDB();

function getPrimaryKeyMapper()
{
    return new PrimaryKeyMapper();
}

function initDB()
{
    require_once(CONNECTOR_DIR.'/../../includes/configure.php');

    $db = Mysql::getInstance();
    
    if (!$db->isConnected()) {
        $db->connect(array(
            "host" => DB_SERVER,
            "user" => DB_SERVER_USERNAME,
            "password" => DB_SERVER_PASSWORD,
            "name" => DB_DATABASE
        ));
    }
}
