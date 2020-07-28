<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\BaseMapper;

class CrossSellingGroup extends BaseMapper
{
    protected $mapperConfig = [
        "table" => "products_xsell_grp_name",
        "query" => "SELECT * FROM products_xsell_grp_name GROUP BY products_xsell_grp_name_id",
        "identity" => "getId",
        "getMethod" => "getCrossSellingGroups",
        "mapPull" => [
            "id" => "products_xsell_grp_name_id",
            "i18ns" => "CrossSellingGroupI18n|addI18n"
        ],
        "mapPush" => [
            "CrossSellingGroupI18n|addI18n" => "i18ns"
        ]
    ];
}
