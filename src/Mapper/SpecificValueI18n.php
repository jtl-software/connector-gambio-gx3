<?php

namespace jtl\Connector\Gambio\Mapper;

class SpecificValueI18n extends \jtl\Connector\Gambio\Mapper\AbstractMapper
{
    protected $mapperConfig = [
        "table" => "feature_value_description",
        "getMethod" => "getI18ns",
        "where" => ["feature_value_id","language_id"],
        "query" => "SELECT feature_value_description.*, languages.code
            FROM feature_value_description
            LEFT JOIN languages ON languages.languages_id=feature_value_description.language_id
            WHERE feature_value_description.feature_value_id=[[feature_value_id]]",
        "mapPull" => [
            "languageISO" => null,
            "specificValueId" => "feature_value_id",
            "value" => "feature_value_text"
        ],
        "mapPush" => [
            "language_id" => null,
            "feature_id" => null,
            "feature_name" => "name"
        ]
    ];

    protected function languageISO($data)
    {
        return $this->fullLocale($data['code']);
    }
}
