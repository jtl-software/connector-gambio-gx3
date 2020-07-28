<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Util\MeasurementUnitHelper;
use \jtl\Connector\Model\GlobalData as GlobalDataModel;
use  \jtl\Connector\Model\Unit as UnitModel;
use  \jtl\Connector\Model\UnitI18n;
use \jtl\Connector\Model\MeasurementUnitI18n;
use \jtl\Connector\Model\MeasurementUnit as MeasurementUnitModel;
use \jtl\Connector\Core\Utilities\Language;
use jtl\Connector\Model\Identity;

class GlobalData extends \jtl\Connector\Gambio\Mapper\BaseMapper
{
    protected $mapperConfig = [
        "mapPull" => [
            "languages" => "Language|addLanguage",
            "customerGroups" => "CustomerGroup|addCustomerGroup",
            "taxRates" => "TaxRate|addTaxRate",
            "currencies" => "Currency|addCurrency",
            //"units" => "Unit|addUnit",
            "crossSellingGroups" => "CrossSellingGroup|addCrossSellingGroup",
            //"measurementUnits" => "MeasurementUnit|addMeasurementUnit",
            "shippingMethods" => "ShippingMethod|addShippingMethod"
        ],
        "mapPush" => [
            "Currency|addCurrency" => "currencies",
            "Unit|addUnit" => "units",
            "CrossSellingGroup|addCrossSellingGroup" => "crossSellingGroups",
            "CustomerGroup|addCustomerGroup" => "customerGroups",
            "MeasurementUnit|addMeasurementUnit" => "measurementUnits"
        ]
    ];

    public function pull($parentData = null, $limit = null)
    {
        /** @var GlobalDataModel $globalData */
        $globalData = $this->generateModel(null);

//        $vpeSQL = 'SELECT pv.products_vpe_id id, pv.products_vpe_name name, l.code as lang FROM products_vpe pv LEFT JOIN languages l ON l.languages_id = pv.language_id';
//        $vpeResult = $this->db->query($vpeSQL);
//        $vpeUnits = [];
//        foreach ($vpeResult as $row) {
//            if (!isset($vpeUnits[$row['id']])) {
//                $vpeUnits[$row['id']] = [];
//            }
//            $vpeUnits[$row['id']][$row['lang']] = $row['name'];
//        }

        $quSQL = 'SELECT qu.quantity_unit_id id, qu.unit_name name, l.code as lang FROM quantity_unit_description qu LEFT JOIN languages l ON l.languages_id = qu.language_id';
        $quResult = $this->db->query($quSQL);
        $quUnits = [];
        foreach ($quResult as $row) {
            if (!isset($quUnits[$row['id']])) {
                $quUnits[$row['id']] = [];
            }
            $quUnits[$row['id']][$row['lang']] = $row['name'];
        }

        $units = self::prepareUnits($quUnits, $this->shopConfig['settings']['DEFAULT_LANGUAGE']);
        foreach ($units as $id => $unit) {
            $gUnitId = new Identity($id);
            $gUnit = (new UnitModel())
                ->setId($gUnitId);


            foreach ($unit as $lang => $name) {
                $gUnit->addI18n(
                    (new UnitI18n())
                        ->setName($name)
                        ->setLanguageISO(Language::convert($lang))
                        ->setUnitId($gUnitId)
                );
            }

            $globalData->addUnit($gUnit);
        }

//        $measurementUnits = self::prepareUnits($vpeUnits, $this->shopConfig['settings']['DEFAULT_LANGUAGE']);
//        foreach ($measurementUnits as $id => $unit) {
//            if(!MeasurementUnitHelper::isUnitByName($id)) {
//                continue;
//            }
//
//            $gMUnitId = new Identity($id);
//            $gMUnit = (new MeasurementUnitModel())->setId($gMUnitId);
//                $gMUnit
//                    ->setCode(MeasurementUnitHelper::getUnitCode($id))
//                    ->addI18n(
//                        (new MeasurementUnitI18n())
//                            ->setLanguageISO(Language::convert($lang))
//                            ->setName($name)
//                            ->setMeasurementUnitId($gMUnitId)
//                    );
//
//                $globalData->addMeasurementUnit($gMUnit);
//        }

        return [$globalData];
    }

    /**
     * @param mixed[] $units
     * @param string $defaultLanguage
     * @return string[]
     */
    public static function prepareUnits(array $units, $defaultLanguage)
    {
        $prepared = [];
        foreach ($units as $id => $unitNames) {
            if (!isset($unitNames[$defaultLanguage])) {
                continue;
            }

            $index = $unitNames[$defaultLanguage];

            $prepared[$index] = $unitNames;
        }

        return $prepared;
    }
}
