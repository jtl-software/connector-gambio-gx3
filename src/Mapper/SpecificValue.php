<?php
namespace jtl\Connector\Gambio\Mapper;

class SpecificValue extends \jtl\Connector\Gambio\Mapper\BaseMapper
{
    protected $mapperConfig = array(
        "table" => "feature_value",
        "query" => "SELECT feature_value.*
            FROM feature_value            
            WHERE feature_value.feature_id=[[feature_id]]",
        "where" => "feature_value_id",
        "identity" => "getId",
        "mapPull" => array(
            "id" => "feature_value_id",
            "specificId" => "feature_id",
            "i18ns" => "SpecificValueI18n|addI18n",
            "sort" => "sort_order"
        ),
        "mapPush" => array(
            "feature_value_id" => "id",
            //"SpecificI18n|addI18n" => "i18ns",
            "sort_order" => "sort"
        )
    );
}
