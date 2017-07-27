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

    public function pull($data, $limit = null)
    {
        if (isset($data['options_id'])) {
            $this->mapperConfig = array(
                "table" => "products_options",
                "query" => 'SELECT * FROM products_options WHERE products_options_id=[[options_id]]',
                "mapPull" => array(
                    "productVariationId" => "products_options_id",
                    "name" => "products_options_name",
                    "languageISO" => null
                )
            );
        }

        return parent::pull($data, $limit);
    }

    protected function languageISO($data)
    {
        return $this->id2locale($data['language_id']);
    }
}
