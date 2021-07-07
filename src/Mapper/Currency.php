<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\BaseMapper;
use jtl\Connector\Gambio\Util\ShopVersion;

class Currency extends BaseMapper
{
    protected $mapperConfig = [
        "table" => "currencies",
        "where" => "currencies_id",
        "identity" => "getId",
        "getMethod" => "getCurrencies",
        "mapPull" => [
            "id" => "currencies_id",
            "name" => "title",
            "factor" => "value",
            "delimiterCent" => "decimal_point",
            "delimiterThousand" => "thousands_point",
            "isDefault" => null,
            "iso" => "code"
        ],
        "mapPush" => [
            "currencies_id" => "id",
            "title" => "name",
            "value" => "factor",
            "decimal_point" => "delimiterCent",
            "thousands_point" => "delimiterThousand",
            "code" => null,
            "decimal_places" => null,
            "symbol_right" => 'iso'
        ]
    ];

    public function push($data, $dbObj = null)
    {
        $currencies = $data->getCurrencies();

        if (!empty($currencies)) {
            foreach ($data->getCurrencies() as $currency) {
                $check = $this->db->query('SELECT currencies_id FROM currencies WHERE code="' . $currency->getIso() . '"');
                if (count($check) > 0) {
                    $currency->getId()->setEndpoint($check[0]['currencies_id']);
                }
            }

            return parent::push($data, $dbObj);
        }
    }

    protected function isDefault($data)
    {
        return $data['code'] == $this->shopConfig['settings']['DEFAULT_CURRENCY'] ? true : false;
    }

    protected function code($data)
    {
        if ($data->getIsDefault() === true) {
            $this->configHelper->updateGxDbConfigValue('DEFAULT_CURRENCY', $data->getIso());
        }

        return $data->getIso();
    }

    protected function decimal_places($data)
    {
        return 2;
    }
}
