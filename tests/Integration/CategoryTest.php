<?php


namespace Tests;


use jtl\Connector\Model\Category;
use jtl\Connector\Model\CategoryAttr;
use jtl\Connector\Model\CategoryAttrI18n;
use jtl\Connector\Model\Identity;

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
    
    public function testCategoryIsActivePush()
    {
        $category = (new Category())
            ->setId(new Identity('', $this->hostId))
            ->setIsActive(false);
        
        $endpointId = $this->pushCoreModels([$category], true)[0]->getId()->getEndpoint();
        $this->assertNotEmpty($endpointId);
        $result = $this->pullCoreModels('Category', 1, $endpointId);
        
        $this->assertNotEquals($category->getIsActive(), $result->getIsActive());
        $this->deleteModel('Category', $endpointId, $this->hostId);
    
        $attribute = (new CategoryAttr())
            ->setCategoryId(new Identity('', 1))
            ->setId(new Identity('', 1));

        $i18n = (new CategoryAttrI18n())
            ->setCategoryAttrId(new Identity('', $this->hostId))
            ->setLanguageISO('ger')
            ->setName('categories_status')
            ->setValue('0');
        $attribute->addI18n($i18n);
        $category->addAttribute($attribute);
    
        $endpointId = $this->pushCoreModels([$category], true)[0]->getId()->getEndpoint();
        $this->assertNotEmpty($endpointId);
        $result = $this->pullCoreModels('Category', 1, $endpointId);
        $this->assertEquals((bool) $category->getAttributes()[0]->getI18ns()[0]->getValue(), $result->getIsActive());
        $this->deleteModel('Category', $endpointId, $this->hostId);
    }
    
    public function testProductAttributePush()
    {
        $ignoreFields = ['id', 'isTranslated', 'isCustomProperty'];
        
        $category = (new Category())
            ->setId(new Identity('', $this->hostId))
            ->setIsActive(true);
        
        $attribute = (new CategoryAttr())
            ->setCategoryId(new Identity('', 1))
            ->setId(new Identity('', 1))
            ->setIsCustomProperty(true)
            ->setIsTranslated(true);
    
        $i18n = (new CategoryAttrI18n())
            ->setCategoryAttrId(new Identity('', 1))
            ->setLanguageISO('ger')
            ->setName('gm_priority')
            ->setValue('99');
        $attribute->addI18n($i18n);
        $category->addAttribute($attribute);
        
        $endpointId = $this->pushCoreModels([$category], true)[0]->getId()->getEndpoint();
        $this->assertNotEmpty($endpointId);
        $result = $this->pullCoreModels('Category', 1, $endpointId);
    
        $this->assertCount(19, $result->getAttributes());
        $category = json_decode($category->getAttributes()[0]->toJson(), true);
        $result = json_decode($result->getAttributes()[11]->toJson(), true);
        
        foreach ($ignoreFields as $ignoreField) {
            $this->recursive_unset($category, $ignoreField);
            $this->recursive_unset($result, $ignoreField);
        }
      
        $this->assertEquals($category, $result);
        $this->deleteModel('Product', $endpointId, $this->hostId);
    }
}
