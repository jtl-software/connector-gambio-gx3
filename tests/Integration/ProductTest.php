<?php


namespace Tests;


use function foo\func;

class ProductTest extends \Jtl\Connector\IntegrationTests\Integration\ProductTest
{
    public function getIgnoreArray()
    {
        return [
        ];
    }
    
    public function testProductCustomerGroupPackagingQuantityPush() {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
    
    public function testProductConfigGroupPush() {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
    
    public function testProductPartsListPush() {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
    
    public function testProductWarehousePush()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
