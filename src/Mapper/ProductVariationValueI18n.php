<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\AbstractMapper;

class ProductVariationValueI18n extends AbstractMapper
{
    protected $mapperConfig = [
        "table" => "properties_values_description",
        "query" => 'SELECT * FROM properties_values_description WHERE properties_values_id=[[properties_values_id]]',
        "getMethod" => "getI18ns",
        "mapPull" => [
            "productVariationValueId" => "properties_values_id",
            "name" => "values_name",
            "languageISO" => null
        ]
    ];

    public function pull($data = null, $limit = null): array
    {
        if (isset($data['options_values_id'])) {
            $this->mapperConfig = [
                "table" => "products_options_values",
                "query" => 'SELECT * FROM products_options_values WHERE products_options_values_id=[[options_values_id]]',
                "getMethod" => "getI18ns",
                "mapPull" => [
                    "productVariationValueId" => "products_options_values_id",
                    "name" => "products_options_values_name",
                    "languageISO" => null
                ]
            ];
        }

        return parent::pull($data, $limit);
    }

    protected function languageISO($data)
    {
        return $this->id2locale($data['language_id']);
    }
}
