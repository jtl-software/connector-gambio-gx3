<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\BaseMapper;

class Language extends BaseMapper
{
    protected $mapperConfig = [
        "table" => "languages",
        "identity" => "getId",
        "mapPull" => [
            "id" => "languages_id",
            "nameEnglish" => "name",
            "nameGerman" => "name",
            "languageISO" => null,
            "isDefault" => null
        ]
    ];

    protected function languageISO($data)
    {
        return $this->fullLocale($data['code']);
    }

    protected function isDefault($data)
    {
        return $data['code'] == $this->shopConfig['settings']['DEFAULT_LANGUAGE'] ? true : false;
    }
}
