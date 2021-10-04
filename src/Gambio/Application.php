<?php

namespace jtl\Connector\Gambio\Gambio;

use Gambio\GX\Application as GxApplication;

/**
 * Class Application
 * @package jtl\Connector\Gambio\Gambio
 */
class Application extends GxApplication
{
    public const
        SERVICE_ORDER_WRITE = 'OrderWrite'
    ;

    /**
     *
     */
    protected function initializeGlobalObjects(): void
    {
        $this->initializeGlobalMainObject();
        $this->initializeGlobalXtcPriceObject();
        $this->initializeGlobalMessageStackObject();
    }
}
