<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\AbstractMapper;
use jtl\Connector\Model\DataModel;

class ProductSpecialPrice extends AbstractMapper
{
    protected $mapperConfig = [
        "table" => "specials",
        "query" => "SELECT * FROM specials WHERE products_id=[[products_id]]",
        "getMethod" => "getSpecialPrices",
        "where" => "specials_id",
        "mapPull" => [
            "id" => "specials_id",
            "productId" => "products_id",
            "isActive" => "status",
            "activeUntilDate" => null,
            "activeFromDate" => null,
            //"stockLimit" => "specials_quantity",
            //"considerStockLimit" => null,
            "considerDateLimit" => null,
            "items" => "ProductSpecialPriceItem|addItem"
        ],
        "mapPush" => [
            //"specials_id" => null,
            "products_id" => "productId",
            "status" => "isActive",
            "expires_date" => "activeUntilDate",
            "begins_date" => "activeFromDate",
            "specials_quantity" => "stockLimit",
            "ProductSpecialPriceItem|addItem|true" => "items"
        ]
    ];
    
    protected function considerDateLimit($data)
    {
        return $data['expires_date'] == '0000-00-00 00:00:00' ? false : true;
    }

    protected function activeUntilDate($data)
    {
        return $data['expires_date'] == '0000-00-00 00:00:00' ? null : $data['expires_date'];
    }
    
    protected function activeFromDate($data)
    {
        return $data['begins_date'] == '0000-00-00 00:00:00' ? null : $data['begins_date'];
    }

    public function push(DataModel $model, \stdClass $dbObj = null)
    {
        $id = $model->getId()->getEndpoint();
        $q='DELETE FROM `specials` WHERE `products_id` = ' . $id;
        $this->db->query($q);

        if (!is_null($model->getSpecialPrices()) && count($model->getSpecialPrices()) === 1) {
            foreach ($model->getSpecialPrices() as $special) {
                $special->setProductId($model->getId());
                $special->setStockLimit(999999);
            }

            return parent::push($model, $dbObj);
        }
    }
}
