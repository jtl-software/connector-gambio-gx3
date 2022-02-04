<?php

define('HTTP_SERVER', '');
define('DIR_WS_CATALOG', '');
define('DIR_FS_DOCUMENT_ROOT', '');
define('DB_SERVER', '');
define('DB_DATABASE', '');
define('DB_SERVER_USERNAME', '');
define('DB_SERVER_PASSWORD', '');
define('DIR_WS_ORIGINAL_IMAGES', '');
define('DIR_WS_THUMBNAIL_IMAGES', '');
define('DIR_WS_INFO_IMAGES', '');
define('DIR_WS_POPUP_IMAGES', '');
define('CONNECTOR_VERSION', '');

class GambioApplicationStub{}
class GambioMainFactoryStub
{
    public static function create($t = null, $t1 = null)
    {
    }
}
class GambioGXCoreLoaderStub
{
    public static function getService($t = null)
    {
    }
}
class GambioTypeStub
{
    public function __construct($t = null)
    {
    }
}

class_alias(GambioApplicationStub::class, '\Gambio\GX\Application');
class_alias(GambioGXCoreLoaderStub::class, '\StaticGXCoreLoader');
class_alias(GambioMainFactoryStub::class, '\MainFactory');
class_alias(GambioTypeStub::class, '\IdType');
class_alias(GambioTypeStub::class, '\StringType');
class_alias(GambioTypeStub::class, '\IntType');
class_alias(GambioTypeStub::class, '\BoolType');