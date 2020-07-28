<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Core\Utilities\Country;

class CustomerOrderShippingAddress extends BaseMapper
{
    protected $mapperConfig = [
        "table" => "customer_orders",
        "getMethod" => "getShippingAddress",
        "mapPull" => [
            "id" => null,
            "customerId" => "customers_id",
            "firstName" => "delivery_firstname",
            "lastName" => "delivery_lastname",
            "company" => "delivery_company",
            "street" => null,
            "extraAddressLine" => "delivery_additional_info",
            "zipCode" => "delivery_postcode",
            "city" => "delivery_city",
            "state" => "delivery_state",
            "countryIso" => null,
            "eMail" => "customers_email_address",
            "phone" => "customers_telephone",
            "salutation" => null
        ],
        "mapPush" => [
            "delivery_name" => null,
            "delivery_firstname" => "firstName",
            "delivery_lastname" => "lastName",
            "delivery_company" => "company",
            "delivery_street_address" => "street",
            "delivery_suburb" => "extraAddressLine",
            "delivery_postcode" => "zipCode",
            "delivery_city" => "city",
            "delivery_state" => "state",
            "delivery_country_iso_code_2" => "countryIso"
        ]
    ];
    
    protected function street($data)
    {
        if ($this->shopConfig['settings']['ACCOUNT_SPLIT_STREET_INFORMATION'] === 1 && !empty($data["delivery_house_number"])) {
            return sprintf("%s %s", $data["delivery_street_address"], $data["delivery_house_number"]);
        } else {
            return $data["delivery_street_address"];
        }
    }
    
    protected function countryIso($data)
    {
        return Country::map(strtolower($data['delivery_country_iso_code_2']));
    }

    protected function salutation($data)
    {
        if ($data['delivery_gender'] == 'm') {
            return 'm';
        } elseif ($data['delivery_gender'] == 'f') {
            return 'w';
        }
    }

    public function pull($data = null, $limit = null)
    {
        return [$this->generateModel($data)];
    }

    protected function id($data)
    {
        return "cID_".$data['customers_id'];
    }

    public function push($parent, $dbObj = null)
    {
        $this->generateDbObj($parent->getShippingAddress(), $dbObj, null, true);
    }

    protected function delivery_name($data)
    {
        return $data->getFirstName().' '.$data->getLastName();
    }
    /*
    protected function street($data)
    {
        return utf8_encode($data['delivery_street_address']);
    }
    */
}
