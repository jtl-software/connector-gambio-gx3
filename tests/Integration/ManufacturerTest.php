<?php


namespace Tests;


class ManufacturerTest extends \Jtl\Connector\IntegrationTests\Integration\ManufacturerTest
{
    public function getIgnoreArray()
    {
        return [
            'i18ns', //Needs fixing
            'sort',
            'urlPath'
        ];
    }
}
