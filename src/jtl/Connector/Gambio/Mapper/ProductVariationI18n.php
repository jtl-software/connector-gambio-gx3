<?php
namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\BaseMapper;

class ProductVariationI18n extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "properties_description",
        "query" => 'SELECT * FROM properties_description WHERE properties_id=[[properties_id]]',
        "mapPull" => array(
            "productVariationId" => "properties_id",
            "name" => "properties_name",
            "languageISO" => null
        )
    );

    protected function languageISO($data)
    {
        return $this->id2locale($data['language_id']);
    }
}
