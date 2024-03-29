<?php

namespace jtl\Connector\Gambio\Mapper;

use \jtl\Connector\Gambio\Mapper\AbstractMapper;

class ProductVariationValue extends AbstractMapper
{
    private $productId;

    protected $mapperConfig = [
        "table" => "products_properties_index",
        "query" => 'SELECT properties_values_id, properties_id, value_sort_order FROM products_properties_index WHERE products_id=[[products_id]] && properties_id=[[properties_id]] GROUP BY properties_values_id, properties_id, value_sort_order',
        "getMethod" => "getValues",
        "mapPull" => [
            "id" => "properties_values_id",
            "productVariationId" => "properties_id",
            "sort" => "value_sort_order",
            "i18ns" => "ProductVariationValueI18n|addI18n"
        ]
    ];

    public function pull($data = null, $limit = null): array
    {
        if (isset($data['options_id'])) {
            $this->mapperConfig = [
                "table" => "products_attributes",
                "query" => 'SELECT * FROM products_attributes WHERE products_id=[[products_id]] && options_id=[[options_id]]',
                "getMethod" => "getValues",
                "mapPull" => [
                    "id" => "options_values_id",
                    "productVariationId" => "options_id",
                    "extraWeight" => null,
                    "sku" => "attributes_model",
                    "sort" => "sortorder",
                    "stockLevel" => "attributes_stock",
                    "i18ns" => "ProductVariationValueI18n|addI18n",
                    "extraCharges" => "ProductVariationValueExtraCharge|addExtraCharge",
                    "ean" => "gm_ean"
                ]
            ];
        }

        return parent::pull($data, $limit);
    }

    protected function extraWeight($data)
    {
        return $data['weight_prefix'] == '-' ? $data['options_values_weight'] * -1 : $data['options_values_weight'];
    }
}
