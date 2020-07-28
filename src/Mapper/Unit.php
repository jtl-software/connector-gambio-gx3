<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\BaseMapper;

class Unit extends BaseMapper
{
    protected $mapperConfig = [
        "query" => "SELECT products_vpe_id FROM products_vpe GROUP BY products_vpe_id",
        "table" => "products_vpe",
        "where" => "products_vpe_id",
        "getMethod" => "getUnits",
        "identity" => "getId",
        "mapPull" => [
            "id" => "products_vpe_id",
            "i18ns" => "UnitI18n|addI18n"
        ],
        "mapPush" => [
            "UnitI18n|addI18n" => "i18ns",
        ]
    ];
}
