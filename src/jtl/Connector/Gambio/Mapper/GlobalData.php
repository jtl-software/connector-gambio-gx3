<?php
namespace jtl\Connector\Gambio\Mapper;

use \jtl\Connector\Model\GlobalData as GlobalDataModel;

class GlobalData extends \jtl\Connector\Gambio\Mapper\BaseMapper
{
    protected $mapperConfig = array(
        "mapPull" => array(
            "languages" => "Language|addLanguage",
            "customerGroups" => "CustomerGroup|addCustomerGroup",
            "taxRates" => "TaxRate|addTaxRate",
            "currencies" => "Currency|addCurrency",
            "units" => "Unit|addUnit",
            "crossSellingGroups" => "CrossSellingGroup|addCrossSellingGroup",
            "measurementUnits" => "MeasurementUnit|addMeasurementUnit",
            "shippingMethods" => "ShippingMethod|addShippingMethod"
        ),
        "mapPush" => array(
            "Currency|addCurrency" => "currencies",
            "Unit|addUnit" => "units",
            "CrossSellingGroup|addCrossSellingGroup" => "crossSellingGroups",
            "CustomerGroup|addCustomerGroup" => "customerGroups",
            "MeasurementUnit|addMeasurementUnit" => "measurementUnits"
        )
    );

    public function pull($parentData = null, $limit = null)
    {
        $globalData = $this->generateModel(null);

        return [$globalData];
    }
}
