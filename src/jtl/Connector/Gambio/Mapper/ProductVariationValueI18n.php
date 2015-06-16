<?php
namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\BaseMapper;

class ProductVariationValueI18n extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "properties_values_description",
        "query" => 'SELECT * FROM properties_values_description WHERE properties_values_id=[[properties_values_id]]',
        "getMethod" => "getI18ns",
        "mapPull" => array(
            "productVariationValueId" => "properties_values_id",
            "name" => "values_name",
            "languageISO" => null
        )
    );

    protected function languageISO($data)
    {
        return $this->id2locale($data['language_id']);
    }
}
