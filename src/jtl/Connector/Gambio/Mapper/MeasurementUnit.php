<?php
namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\BaseMapper;

class MeasurementUnit extends BaseMapper
{
    protected $mapperConfig = array(
        "query" => "SELECT quantity_unit_id FROM quantity_unit",
        "table" => "quantity_unit",
        "where" => "quantity_unit_id",
        "getMethod" => "getMeasurementUnits",
        "identity" => "getId",
        "mapPull" => array(
            "id" => "quantity_unit_id",
            "i18ns" => "MeasurementUnitI18n|addI18n"
        ),
        "mapPush" => array(
        	"MeasurementUnitI18n|addI18n" => "i18ns"
        )
    );
}
