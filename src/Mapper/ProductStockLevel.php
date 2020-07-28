<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\BaseMapper;
use jtl\Connector\Model\ProductStockLevel as ProductStockLevelModel;

class ProductStockLevel extends BaseMapper
{
    public function pull($data = null, $limit = null)
    {
        $stockLevel = new ProductStockLevelModel();
        $stockLevel->setProductId($this->identity($data['products_id']));
        $stockLevel->setStockLevel(floatval($data['products_quantity']));

        return [$stockLevel];
    }

    public function push($stockLevel, $dbObj = null)
    {
        $productId = $stockLevel->getProductId()->getEndpoint();

        if (!empty($productId)) {
            if (strpos($productId, '_') !== false) {
                $ids = explode('_', $productId);

                $this->db->query('UPDATE products_properties_combis SET combi_quantity='.round($stockLevel->getStockLevel()).' WHERE products_properties_combis_id='.$ids[1]);
            } else {
                $this->db->query('UPDATE products SET products_quantity='.round($stockLevel->getStockLevel()).' WHERE products_id='.$productId);
            }

            return $stockLevel;
        }

        return false;
    }
}
