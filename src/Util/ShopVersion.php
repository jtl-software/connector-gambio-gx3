<?php

namespace jtl\Connector\Gambio\Util;

/**
 * Class ShopVersion
 * @package jtl\Connector\Gambio\Util
 */
class ShopVersion
{
    /**
     * @var
     */
    protected static $shopVersion;

    /**
     * @param $version
     * @return bool|int
     */
    public static function isGreaterOrEqual($version)
    {
        return version_compare(ShopVersion::$shopVersion, $version, '>=');
    }

    /**
     * @param $version
     */
    public static function setShopVersion($version)
    {
        ShopVersion::$shopVersion = $version;
    }
}