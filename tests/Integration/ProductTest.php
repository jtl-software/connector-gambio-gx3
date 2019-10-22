<?php


namespace Tests;


use DateTime;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product;
use jtl\Connector\Model\ProductI18n;
use jtl\Connector\Model\ProductPrice;
use jtl\Connector\Model\ProductPriceItem;
use jtl\Connector\Model\ProductStockLevel;
use function foo\func;

class ProductTest extends \Jtl\Connector\IntegrationTests\Integration\ProductTest
{
    public function getIgnoreArray()
    {
        return [
            'asin',
            'height',
            'isNewProduct',
            'modified',
            'note',
            'originCountry',
            'purchasePrice',
            'recommendedRetailPrice',
            'serialNumber',
            'shippingWeight',
            'taric',
            'unNumber',
            'creationDate', //Endpoint test /is overwritten if isNewProduct = true
            'isBatch',
            'isBestBefore',
            'isSerialNumber',
            'isDivisible',
            'minBestBeforeDate',
            'minBestBeforeQuantity',
            'nextAvailableInflowDate',
            'nextAvailableInflowQuantity',
            'newReleaseDate',
            'basePriceFactor',
            'basePriceQuantity',
            'basePriceUnitCode',
            'keywords', //Endpoint test /is handled via attribute to include multilingual values
            'length',
            'measurementUnitCode',
            'measurementQuantity',
            'supplierDeliveryTime',
            'supplierStockLevel',
            'minimumQuantity',
            'i18ns.0.measurementUnitName',
            'prices.0.items.0.quantity',
            'i18ns.0.unitName',
            'attributes', //Endpoint test
            'isActive', //Endpoint test /is handled via attribute
            'packagingQuantity', //Endpoint test
            'permitNegativeStock', //Endpoint test
            'vat', //Endpoint test
            'basePriceUnitName', //Endpoint test
            'specialPrices.0.stockLimit',
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
    
    public function testProductInvisibilityPush()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
    
    public function testProductSpecificPush()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
    
    public function testProductVariationPush()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
    
    public function testProductVarCombinationPush()
    {
        $this->markTestIncomplete(
            'This test needs Fixing. Sending childs before parents'
        );
    }
    
    public function testProductCreationDate()
    {
        $product = (new Product())
            ->setCreationDate(new DateTime('2019-08-21T00:00:00+0200'))
            ->setisNewProduct(false)
            ->setStockLevel(new ProductStockLevel())
            ->setId(new Identity('', $this->hostId));
    
        if ($product->getMinimumOrderQuantity() == 0) {
            $product->setMinimumOrderQuantity(1);
        }
        
        $endpointId = $this->pushCoreModels([$product], true)[0]->getId()->getEndpoint();
        $this->assertNotEmpty($endpointId);
        $result = $this->pullCoreModels('Product', 1, $endpointId);
    
        $this->assertEquals($product->getCreationDate(), $result->getCreationDate());
        $this->deleteModel('Product', $endpointId, $this->hostId);
        
        $product->setisNewProduct(true)
            ->setNewReleaseDate(new DateTime('2019-08-26T00:00:00+0200'));
    
        $endpointId = $this->pushCoreModels([$product], true)[0]->getId()->getEndpoint();
        $this->assertNotEmpty($endpointId);
        $result = $this->pullCoreModels('Product', 1, $endpointId);
    
        $this->assertNotEquals($product->getCreationDate(), $result->getCreationDate());
        $this->assertEquals($product->getNewReleaseDate(), $result->getCreationDate());
        $this->deleteModel('Product', $endpointId, $this->hostId);
    }
}
