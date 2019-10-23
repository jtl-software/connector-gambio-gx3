<?php


namespace Tests;


use DateTime;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product;
use jtl\Connector\Model\ProductAttr;
use jtl\Connector\Model\ProductAttrI18n;
use jtl\Connector\Model\ProductStockLevel;

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
            'permitNegativeStock', //Endpoint test? /is set in relation to the shop config
            'vat', //Endpoint test? /is set in relation to the shop config
            'basePriceUnitName',
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
    
    public function testProductCreationDatePush()
    {
        $product = (new Product())
            ->setCreationDate(new DateTime('2019-08-21T00:00:00+0200'))
            ->setisNewProduct(false)
            ->setStockLevel(new ProductStockLevel())
            ->setId(new Identity('', $this->hostId))
            ->setMinimumOrderQuantity(1);
        
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
    
    public function testProductKeywordsPush()
    {
        $product = (new Product())
            ->setStockLevel(new ProductStockLevel())
            ->setCreationDate(new DateTime('2019-08-21T00:00:00+0200'))
            ->setId(new Identity('', $this->hostId))
            ->setMinimumOrderQuantity(1)
            ->setKeywords('testKeyWords');
    
        $endpointId = $this->pushCoreModels([$product], true)[0]->getId()->getEndpoint();
        $this->assertNotEmpty($endpointId);
        $result = $this->pullCoreModels('Product', 1, $endpointId);
    
        $this->assertNotEquals($product->getKeywords(), $result->getKeywords());
        $this->deleteModel('Product', $endpointId, $this->hostId);
        
        $product->setKeywords('');
    
        $attribute = (new ProductAttr())
            ->setId(new Identity('', 1))
            ->setProductId(new Identity('', $this->hostId))
            ->setIsCustomProperty(true)
            ->setIsTranslated(true);
    
        $attributeI18n = (new ProductAttrI18n())
            ->setProductAttrId(new Identity('', 1))
            ->setLanguageISO('ger')
            ->setName('products_keywords')
            ->setValue('testKeyWords');
        
        $attribute->setI18ns([$attributeI18n]);
        $product->setAttributes([$attribute]);
    
        $endpointId = $this->pushCoreModels([$product], true)[0]->getId()->getEndpoint();
        $this->assertNotEmpty($endpointId);
        $result = $this->pullCoreModels('Product', 1, $endpointId);
    
        $this->assertEquals($product->getAttributes()[0]->getI18ns()[0]->getValue(), $result->getKeywords());
        $this->deleteModel('Product', $endpointId, $this->hostId);
    }
    
    public function testProductPackagingQuantityPush()
    {
        $product = (new Product())
            ->setStockLevel(new ProductStockLevel())
            ->setCreationDate(new DateTime('2019-08-21T00:00:00+0200'))
            ->setId(new Identity('', $this->hostId))
            ->setMinimumOrderQuantity(1)
            ->setPackagingQuantity(1.55);
    
        $endpointId = $this->pushCoreModels([$product], true)[0]->getId()->getEndpoint();
        $this->assertNotEmpty($endpointId);
        $result = $this->pullCoreModels('Product', 1, $endpointId);
    
        $this->assertEquals($product->getPackagingQuantity(), $result->getPackagingQuantity());
        $this->deleteModel('Product', $endpointId, $this->hostId);
        
        $product = (new Product())
            ->setStockLevel(new ProductStockLevel())
            ->setCreationDate(new DateTime('2019-08-21T00:00:00+0200'))
            ->setId(new Identity('', $this->hostId))
            ->setMinimumOrderQuantity(1)
            ->setPackagingQuantity(0);
    
        $endpointId = $this->pushCoreModels([$product], true)[0]->getId()->getEndpoint();
        $this->assertNotEmpty($endpointId);
        $result = $this->pullCoreModels('Product', 1, $endpointId);
    
        $this->assertNotEquals($product->getPackagingQuantity(), $result->getPackagingQuantity());
        $this->assertEquals(1, $result->getPackagingQuantity());
        $this->deleteModel('Product', $endpointId, $this->hostId);
    }
    
    public function testProductIsActivePush()
    {
        $product = (new Product())
            ->setStockLevel(new ProductStockLevel())
            ->setCreationDate(new DateTime('2019-08-21T00:00:00+0200'))
            ->setId(new Identity('', $this->hostId))
            ->setMinimumOrderQuantity(1)
            ->setIsActive(false);
    
        $endpointId = $this->pushCoreModels([$product], true)[0]->getId()->getEndpoint();
        $this->assertNotEmpty($endpointId);
        $result = $this->pullCoreModels('Product', 1, $endpointId);
    
        $this->assertNotEquals($product->getIsActive(), $result->getIsActive());
        $this->deleteModel('Product', $endpointId, $this->hostId);
    
        $attribute = (new ProductAttr())
            ->setProductId(new Identity('', 1))
            ->setId(new Identity('', 1));
    
        $i18n = (new ProductAttrI18n())
            ->setProductAttrId(new Identity('', $this->hostId))
            ->setLanguageISO('ger')
            ->setName('products_status')
            ->setValue('0');
        $attribute->addI18n($i18n);
        $product->addAttribute($attribute);
    
        $endpointId = $this->pushCoreModels([$product], true)[0]->getId()->getEndpoint();
        $this->assertNotEmpty($endpointId);
        $result = $this->pullCoreModels('Product', 1, $endpointId);
    
        $this->assertEquals((bool) $product->getAttributes()[0]->getI18ns()[0]->getValue(), $result->getIsActive());
        $this->deleteModel('Product', $endpointId, $this->hostId);
    }
}
