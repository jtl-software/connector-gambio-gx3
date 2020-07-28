<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 17.10.2018
 * Time: 14:50
 */
namespace jtl\Connector\Gambio\Util;

final class MeasurementUnitHelper
{
    protected static $measurementUnits = [
        'm' => ['factor' => 1, 'lang' => ['ger' => 'Meter', 'eng' => 'Meter']],
        'mm' => ['factor' => 1E-3, 'lang' => ['ger' => 'Millimeter', 'eng' => 'Millimeter']],
        'cm' => ['factor' => 1E-2, 'lang' => ['ger' => 'Zentimeter', 'eng' => 'Centimeter']],
        'dm' => ['factor' => 1E-1, 'lang' => ['ger' => 'Dezimeter', 'eng' => 'Decimeter']],
        'in' => ['factor' => 1, 'lang' => ['ger' => 'Zoll', 'eng' => 'Inch']],
        'km' => ['factor' => 1E3, 'lang' => ['ger' => 'Kilometer', 'eng' => 'Kilometer']],
        'kg' => ['factor' => 1E3, 'lang' => ['ger' => 'Kilogramm', 'eng' => 'Kilogram']],
        'mg' => ['factor' => 1E-3, 'lang' => ['ger' => 'Milligramm', 'eng' => 'Milligram']],
        'g' => ['factor' => 1, 'lang' => ['ger' => 'Gramm', 'eng' => 'Gram']],
        'lb' => ['factor' => 0.5E3, 'lang' => ['ger' => 'Pfund', 'eng' => 'Pound']],
        't' => ['factor' => 1E6, 'lang' => ['ger' => 'Tonne', 'eng' => 'Ton']],
        'm2' => ['factor' => 1, 'lang' => ['ger' => 'Quadratmeter', 'eng' => 'Square meter']],
        'mm2' => ['factor' => 1E-3, 'lang' => ['ger' => 'Quadratmillimeter', 'eng' => 'Square millimeter']],
        'cm2' => ['factor' => 1E-2, 'lang' => ['ger' => 'Quadratzentimeter', 'eng' => 'Square centimeter']],
        'L' => ['factor' => 1, 'lang' => ['ger' => 'Liter', 'eng' => 'Liter']],
        'mL' => ['factor' => 1E-3, 'lang' => ['ger' => 'Milliliter', 'eng' => 'Millimeter']],
        'cL' => ['factor' => 1E-2, 'lang' => ['ger' => 'Zentiliter', 'eng' => 'Centiliter']],
        'dL' => ['factor' => 1E-1, 'lang' => ['ger' => 'Deziliter', 'eng' => 'Deciliter']],
        'm3' => ['factor' => 1, 'lang' => ['ger' => 'Kubikmeter', 'eng' => 'Cubic meter']],
        'cm3' => ['factor' => 1E-2, 'lang' => ['ger' => 'Kubikzentimeter', 'eng' => 'Cubic centimeter']],
        'dm3' => ['factor' => 1E-1, 'lang' => ['ger' => 'Kubikdezimeter', 'eng' => 'Cubic decimeter']]
    ];

    /**
     * @param string $unit
     * @return boolean
     */
    public static function isUnit($unit)
    {
        foreach (self::$measurementUnits as $code => $data) {
            if (strtolower($unit) === strtolower($code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $name
     * @return boolean
     */
    public static function isUnitByName($name)
    {
        foreach (self::$measurementUnits as $code => $data) {
            foreach ($data['lang'] as $lang => $mUnitName) {
                if (strtolower($name) === strtolower($mUnitName)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param string $unit
     * @return mixed[]
     */
    public static function getUnit($unit)
    {
        foreach (self::$measurementUnits as $code => $data) {
            if (strtolower($unit) === strtolower($code)) {
                return array_merge(['code' => $code], self::$measurementUnits[$code]);
            }
        }
        throw new \RuntimeException($unit . ' is not a measurement unit!');
    }

    /**
     * @param string $unit
     * @return string[]
     */
    public static function getUnitNames($unit)
    {
        foreach (self::$measurementUnits as $code => $data) {
            if (strtolower($unit) === strtolower($code)) {
                return $data['lang'];
            }
        }
        throw new \RuntimeException($unit . ' is not a measurement unit!');
    }

    /**
     * @param string $unit
     * @param string $lang
     * @return string
     */
    public static function getUnitName($unit, $lang = 'ger')
    {
        $names = self::getUnitNames($unit);
        return isset($names[$lang]) ? $names[$lang] : reset($names);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function getUnitCode($name)
    {
        foreach (self::$measurementUnits as $code => $data) {
            foreach ($data['lang'] as $lang => $mUnitName) {
                if (strtolower($name) === strtolower($mUnitName)) {
                    return $code;
                }
            }
        }
        throw new \RuntimeException($name . ' is not a known measurement unit name!');
    }

    /**
     * @param string $unit
     * @return integer
     */
    public static function getUnitFactor($unit)
    {
        $data = self::getUnit($unit);
        return $data['factor'];
    }

    /**
     * @param string $unit
     * @return boolean
     */
    public static function isCombinedUnit($unit)
    {
        $value = (float)$unit;
        return !empty($value) && $unit !== (string)($value);
    }

    /**
     * @param string $unit
     * @return mixed[]
     */
    public static function splitCombinedUnit($unit)
    {
        if (!static::isCombinedUnit($unit)) {
            throw new \RuntimeException($unit . ' is not a combined unit!');
        }

        $value = (float)$unit;
        $unit = trim(substr($unit, strlen((string)$value)));
        return ['quantity' => $value, 'unit' => $unit];
    }
}
