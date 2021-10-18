<?php

namespace jtl\Connector\Gambio\Mapper;

class CustomerGroup extends AbstractMapper
{
    protected $mapperConfig = [
        "table" => "customers_status",
        "query" => "SELECT customers_status_id, customers_status_discount, customers_status_show_price_tax
          FROM customers_status
          GROUP BY customers_status_id, customers_status_discount, customers_status_show_price_tax",
        "identity" => "getId",
        "getMethod" => "getCustomerGroups",
        "mapPull" => [
            "id" => "customers_status_id",
            "discount" => "customers_status_discount",
            "applyNetPrice" => null,
            "isDefault" => null,
            "i18ns" => "CustomerGroupI18n|addI18n",
            "attributes" => "CustomerGroupAttr|addAttribute"
        ],
        "mapPush" => [
            "CustomerGroupI18n|addI18n" => "i18ns"
        ]
    ];

    protected function isDefault($data)
    {
        return ($data['customers_status_id'] == $this->shopConfig['settings']['DEFAULT_CUSTOMERS_STATUS_ID']) ? true : false;
    }

    protected function applyNetprice($data)
    {
        return $data['customers_status_show_price_tax'] == 1 ? false : true;
    }
}
