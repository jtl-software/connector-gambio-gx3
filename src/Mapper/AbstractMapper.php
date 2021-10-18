<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Core\Database\IDatabase;
use jtl\Connector\Gambio\Util\ConfigHelper;

/**
 * Class AbstractMapper
 * @package jtl\Connector\Gambio\Mapper
 */
abstract class AbstractMapper extends \Jtl\Connector\XtcComponents\AbstractMapper
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * AbstractMapper constructor.
     * @param IDatabase $db
     * @param array $shopConfig
     * @param \stdClass $connectorConfig
     * @throws \Exception
     */
    public function __construct(IDatabase $db, array $shopConfig, \stdClass $connectorConfig)
    {
        parent::__construct($db, $shopConfig, $connectorConfig);
        $this->configHelper = new ConfigHelper($db);
    }

    /**
     * @return string
     */
    protected function getShopName(): string
    {
        return "gambio";
    }

    /**
     * @return string
     */
    protected function getMainNamespace(): string
    {
        return "jtl\\Connector\\Gambio";
    }
}
