<?php
namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\BaseMapper;

class MeasurementUnit extends BaseMapper
{
    protected static $measurementUnits = [
        'm' => ['ger' => 'Meter', 'eng' => 'Meter'],
        'mm' => ['ger' => 'Millimeter', 'eng' => 'Millimeter'],
        'cm' => ['ger' => 'Zentimeter', 'eng' => 'Centimeter'],
        'dm' => ['ger' => 'Dezimeter', 'eng' => 'Decimeter'],
        'in' => ['ger' => 'Zoll', 'eng' => 'Inch'],
        'km' => ['ger' => 'Kilometer', 'eng' => 'Kilometer'],
        'kg' => ['ger' => 'Kilogramm', 'eng' => 'Kilogram'],
        'mg' => ['ger' => 'Milligramm', 'eng' => 'Milligram'],
        'g' => ['ger' => 'Gramm', 'eng' => 'Gram'],
        'lb' => ['ger' => 'Pfund', 'eng' => 'Pound'],
        't' => ['ger' => 'Tonne', 'eng' => 'Ton'],
        'm2' => ['ger' => 'Quadratmeter', 'eng' => 'Square meter'],
        'mm2' => ['ger' => 'Quadratmillimeter', 'eng' => 'Square millimeter'],
        'cm2' => ['ger' => 'Quadratzentimeter', 'eng' => 'Square centimeter'],
        'L' => ['ger' => 'Liter', 'eng' => 'Liter'],
        'mL' => ['ger' => 'Milliliter', 'eng' => 'Millimeter'],
        'dL' => ['ger' => 'Deziliter', 'eng' => 'Deciliter'],
        'cL' => ['ger' => 'Zentiliter', 'eng' => 'Centiliter'],
        'm3' => ['ger' => 'Kubikmeter', 'eng' => 'Cubic meter'],
        'cm3' => ['ger' => 'Kubikzentimeter', 'eng' => 'Cubic centimeter'],
        'dm2' => ['ger' => 'Kubikdezimeter', 'eng' => 'Cubic decimeter']
    ];

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


    /**
     * @param string $name
     * @return boolean
     */
    public static function isMeasurementUnitByName($name)
    {
        foreach (self::$measurementUnits as $code => $langs) {
            foreach($langs as $lang => $mUnitName) {
                if(strtolower($name) === strtolower($mUnitName)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param string $unit
     * @return boolean
     */
    public static function isMeasurementUnitByCode($unit)
    {
        foreach (self::$measurementUnits as $code => $langs) {
            if(strtolower($unit) === strtolower($code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $unit
     * @return string
     */
    public static function getMeasurementUnitNames($unit)
    {
        foreach (self::$measurementUnits as $code => $langs) {
            if(strtolower($unit) === strtolower($code)) {
                return $langs;
            }
        }
        throw new \RuntimeException($unit . ' is not a measurement unit!');
    }

    /**
     * @param string $unit
     * @param string $lang
     * @return string
     */
    public static function getMeasurementUnitName($unit, $lang = 'ger')
    {
        $names = self::getMeasurementUnitNames($unit);
        return isset($names[$lang]) ? $names[$lang] : reset($names);
    }

    /**
     * @param string $name
     * @return string
     */
    public function getMeasurementUnitCode($name)
    {
        foreach (self::$measurementUnits as $code => $langs) {
            foreach ($langs as $lang => $mUnitName) {
                if (strtolower($name) === strtolower($mUnitName)) {
                    return $code;
                }
            }
        }
        throw new \RuntimeException($name . ' is not a known measurement unit name!');
    }
}
