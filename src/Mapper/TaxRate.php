<?php

namespace jtl\Connector\Gambio\Mapper;

class TaxRate extends AbstractMapper
{
    protected $mapperConfig = [
        "table" => "tax_rates",
        "query" => "SELECT tax_rate FROM tax_rates GROUP BY tax_rate",
        "mapPull" => [
            //"id" => "tax_rates_id",
            "rate" => "tax_rate"
        ]
    ];
}
