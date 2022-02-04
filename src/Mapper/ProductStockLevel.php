<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\AbstractMapper;
use jtl\Connector\Model\DataModel;
use jtl\Connector\Model\ProductStockLevel as ProductStockLevelModel;
use stdClass;

class ProductStockLevel extends AbstractMapper
{
    public function pull($data = null, $limit = null): array
    {
        $stockLevel = new ProductStockLevelModel();
        $stockLevel->setProductId($this->identity($data['products_id']));
        $stockLevel->setStockLevel(floatval($data['products_quantity']));

        return [$stockLevel];
    }

    public function push(DataModel $model, \stdClass $dbObj = null)
    {
        $productId = $model->getProductId()->getEndpoint();

        if (!empty($productId)) {
            $stockLevel = (int)round($model->getStockLevel());

            if (strpos($productId, '_') !== false) {
                $ids = explode('_', $productId);

                $this->db->query(sprintf('UPDATE products_properties_combis SET combi_quantity = %d WHERE products_properties_combis_id = %d', $stockLevel, $ids[1]));
            } else {
                $this->db->query(sprintf('UPDATE products SET products_quantity = %d WHERE products_id= %d', $stockLevel, $productId));

                $specialPriceQuery = sprintf('SELECT `products_id` FROM `specials` WHERE `products_id` = %d', $productId);
                $specialPriceResult = $this->db->query($specialPriceQuery);
                if(count($specialPriceResult) > 0) {
                    $specialPriceObj = new \stdClass();
                    $specialPriceObj->specials_quantity = ((int) $this->shopConfig['settings']['STOCK_CHECK'] === 0 || (int) $this->shopConfig['settings']['STOCK_ALLOW_CHECKOUT'] === 1) ? 9999999 : $stockLevel;
                    $this->db->updateRow($specialPriceObj, 'specials', 'products_id', $productId);
                }
            }

            return $stockLevel;
        }

        return false;
    }
}
