<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Model\DataModel;
use jtl\Connector\Model\ProductStockLevel as ProductStockLevelModel;

/**
 *
 */
class ProductStockLevel extends AbstractMapper
{
    /**
     * @param $parentData
     * @param $limit
     * @return ProductStockLevelModel[]
     */
    public function pull($parentData = null, $limit = null): array
    {
        return [
            (new ProductStockLevelModel())
                ->setProductId($this->identity($parentData['products_id']))
                ->setStockLevel((float)$parentData['products_quantity'])
        ];
    }

    /**
     * @param DataModel $model
     * @param \stdClass|null $dbObj
     * @return false|int
     */
    public function push(DataModel $model, \stdClass $dbObj = null)
    {
        /** @var ProductStockLevelModel $model */
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
