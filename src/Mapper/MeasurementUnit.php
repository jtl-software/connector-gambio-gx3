<?php

namespace jtl\Connector\Gambio\Mapper;

class MeasurementUnit extends BaseMapper
{
    protected $mapperConfig = [
        "query" => "SELECT quantity_unit_id FROM quantity_unit",
        "table" => "quantity_unit",
        "where" => "quantity_unit_id",
        "getMethod" => "getMeasurementUnits",
        "identity" => "getId",
        "mapPull" => [
            "id" => "quantity_unit_id",
            "i18ns" => "MeasurementUnitI18n|addI18n"
        ],
        "mapPush" => [
            "MeasurementUnitI18n|addI18n" => "i18ns"
        ]
    ];
}
