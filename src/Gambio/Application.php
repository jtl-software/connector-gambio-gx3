<?php


namespace jtl\Connector\Gambio\Gambio;


use Gambio\GX\Application as GxApplication;

class Application extends GxApplication
{
    protected function initializeGlobalObjects()
    {
        $this->initializeGlobalMainObject();
        $this->initializeGlobalXtcPriceObject();
        //$this->setSessionObjects();
        $this->initializeGlobalMessageStackObject();
    }
}