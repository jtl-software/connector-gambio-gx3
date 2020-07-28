<?php

namespace jtl\Connector\Gambio\Installer;

use jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Gambio\Util\ConfigHelper;

abstract class Module
{
    /**
     * @var Mysql
     */
    protected $db;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var array
     */
    protected $shopConfig;

    public static $name = null;

    /**
     * Module constructor.
     * @param Mysql $db
     * @param Config $config
     * @param ConfigHelper $configHelper
     * @param array $shopConfig
     */
    public function __construct(Mysql $db, Config $config, ConfigHelper $configHelper, array $shopConfig)
    {
        $this->db = $db;
        $this->config = $config;
        $this->configHelper = $configHelper;
        $this->shopConfig = $shopConfig;
    }

    abstract public function form();

    abstract public function save();
}
