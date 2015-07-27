<?php
namespace jtl\Connector\Gambio\Mapper;

class CustomerGroup extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "customers_status",
        "query" => "SELECT * FROM customers_status WHERE customers_status_id != 0 GROUP BY customers_status_id",
        "identity" => "getId",
        "getMethod" => "getCustomerGroups",
        "mapPull" => array(
            "id" => "customers_status_id",
            "discount" => "customers_status_discount",
            "applyNetPrice" => null,
            "isDefault" => null,
            "i18ns" => "CustomerGroupI18n|addI18n",
            "attributes" => "CustomerGroupAttr|addAttribute"
        ),
        "mapPush" => array(
            "CustomerGroupI18n|addI18n" => "i18ns"
        )
    );

    protected function isDefault($data)
    {
        return ($data['customers_status_id'] == $this->shopConfig['settings']['DEFAULT_CUSTOMERS_STATUS_ID']) ? true : false;
    }

    protected function applyNetprice($data)
    {
        return $data['customers_status_show_price_tax'] == 1 ? false : true;
    }
}
