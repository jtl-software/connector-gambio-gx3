<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\BaseMapper;

class ProductSpecialPrice extends BaseMapper
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
            //"specials_quantity" => "stockLimit",
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

    public function push($parent, $dbObj = null)
    {
        $id = $parent->getId()->getEndpoint();
        $q='DELETE FROM `specials` WHERE `products_id` = ' . $id;
        $this->db->query($q);

        if (!is_null($parent->getSpecialPrices()) && count($parent->getSpecialPrices()) === 1) {
            foreach ($parent->getSpecialPrices() as $special) {
                $special->setProductId($parent->getId());
            }

            return parent::push($parent, $dbObj);
        }
    }
}
