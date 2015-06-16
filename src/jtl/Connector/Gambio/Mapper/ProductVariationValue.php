<?php
namespace jtl\Connector\Gambio\Mapper;

use \jtl\Connector\Gambio\Mapper\BaseMapper;

class ProductVariationValue extends BaseMapper
{
    private $productId;

    protected $mapperConfig = array(
        "table" => "products_properties_index",
        "query" => 'SELECT * FROM products_properties_index WHERE products_id=[[products_id]] && properties_id=[[properties_id]] GROUP BY properties_values_id',
        "getMethod" => "getValues",
        "mapPull" => array(
            "id" => "properties_values_id",
            "productVariationId" => "properties_id",
            //"sku" => "attributes_model",
            "sort" => "value_sort_order",
            //"stockLevel" => "attributes_stock",
            "i18ns" => "ProductVariationValueI18n|addI18n"
        )
    );

    protected function extraWeight($data)
    {
        return $data['weight_prefix'] == '-' ? $data['options_values_weight'] * -1 : $data['options_values_weight'];
    }
}
