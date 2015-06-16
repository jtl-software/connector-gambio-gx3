<?php
namespace jtl\Connector\Gambio\Mapper;

use \jtl\Connector\Gambio\Mapper\BaseMapper;
use \jtl\Connector\Linker\ChecksumLinker;
use \jtl\Connector\Core\Logger\Logger;

class ProductVariation extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_options",
        "query" => 'SELECT * FROM products_properties_index WHERE products_id=[[products_id]] GROUP BY properties_id',
        "where" => "products_properties_combis_id",
        "getMethod" => "getVariations",
        "mapPull" => array(
            "id" => "properties_id",
            "productId" => "products_id",
            "sort" => "properties_sort_order",
            "i18ns" => "ProductVariationI18n|addI18n",
            "values" => "ProductVariationValue|addValue"
        )
    );

    public function push($parent, $dbObj = null)
    {
        return $parent->getVariations();
    }
}
