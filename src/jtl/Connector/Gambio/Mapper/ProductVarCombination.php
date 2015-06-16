<?php
namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\BaseMapper;

class ProductVarCombination extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_properties_index",
        "query" => "SELECT products_id,properties_id,properties_values_id FROM products_properties_index WHERE products_id=[[products_id]] GROUP BY properties_values_id",
        "mapPull" => array(
            "productId" => "products_id",
            "productVariationId" => "properties_id",
            "productVariationValueId" => "properties_values_id"
        )        
    );
}
