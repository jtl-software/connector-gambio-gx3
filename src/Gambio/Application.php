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

    public function run()
    {
        $this->registerComposerAutoloader();
        $this->runGProtector();
        self::loadConfig();
        $this->setUpEnvironment();
        $this->initLanguage();
        $this->updateSessionData();
        $this->initializeGlobalObjects();
    }

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
