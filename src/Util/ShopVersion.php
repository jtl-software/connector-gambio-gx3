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
     * @return boolean
     * @throws \Exception
     */
    public static function isGreaterOrEqual($version): bool
    {
        if(is_null(static::$shopVersion)) {
            throw new \Exception('Shop version is not set');
        }
        return version_compare(static::$shopVersion, $version, '>=');
    }

    /**
     * @param $version
     */
    public static function setShopVersion($version)
    {
        ShopVersion::$shopVersion = $version;
    }
}