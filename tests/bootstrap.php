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
    $session = new SessionHelper("gambio");
    
    if (!isset($session->shopConfig)) {
        $session->shopConfig = readConfigFile();
    }
    if (!isset($session->connectorConfig)) {
        $session->connectorConfig = json_decode(@file_get_contents(TEST_DIR.'/config/config.json'));
    }
    
    $db = Mysql::getInstance();
    
    if (!$db->isConnected()) {
        $db->connect(array(
            "host" => $session->shopConfig['db']["host"],
            "user" => $session->shopConfig['db']["user"],
            "password" => $session->shopConfig['db']["pass"],
            "name" => $session->shopConfig['db']["name"]
        ));
    }
}

function readConfigFile()
{
    $gx_version = "";
    require_once(CONNECTOR_DIR.'/../../includes/configure.php');
    require_once(CONNECTOR_DIR.'/../../release_info.php');
    
    return array(
        'shop' => array(
            'url' => HTTP_SERVER,
            'folder' => DIR_WS_CATALOG,
            'path' => DIR_FS_DOCUMENT_ROOT,
            'fullUrl' => HTTP_SERVER.DIR_WS_CATALOG,
            'version' => ltrim($gx_version,'v')
        ),
        'db' => array(
            'host' => DB_SERVER,
            'name' => DB_DATABASE,
            'user' => DB_SERVER_USERNAME,
            'pass' => DB_SERVER_PASSWORD
        ),
        'img' => array(
            'original' => DIR_WS_ORIGINAL_IMAGES,
            'thumbnails' => DIR_WS_THUMBNAIL_IMAGES,
            'info' => DIR_WS_INFO_IMAGES,
            'popup' => DIR_WS_POPUP_IMAGES,
            'gallery' => 'images/product_images/gallery_images/'
        )
    );
}
