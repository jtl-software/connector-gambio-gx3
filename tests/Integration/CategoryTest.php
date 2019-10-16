<?php


namespace Tests;


class CategoryTest extends \Jtl\Connector\IntegrationTests\Integration\CategoryTest
{
    public function getIgnoreArray()
    {
        return [
            'attributes', //Endpoint test
            'level',
            'isActive' //Endpoint test /is handled via attribute
        ];
    }
    
    public function testCategoryCustomGroupsPush()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
    
    public function testCategoryInvisibilitiesPush()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
