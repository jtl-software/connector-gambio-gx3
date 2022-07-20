<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Formatter\ExceptionFormatter;
use jtl\Connector\Gambio\Connector;
use \jtl\Connector\Model\ProductVariation as ProductVariationModel;
use \jtl\Connector\Core\Logger\Logger;

class ProductVariation extends Product
{
    public const
        DISPLAY_TYPE_DROPDOWN = 'Dropdown',
        DISPLAY_TYPE_RADIO = 'Radio',
        DISPLAY_TYPE_TEXT = 'Text';

    protected static $variationTypeMapping = [
        ProductVariationModel::TYPE_RADIO => self::DISPLAY_TYPE_RADIO,
        ProductVariationModel::TYPE_TEXTBOX => self::DISPLAY_TYPE_TEXT,
        ProductVariationModel::TYPE_SELECT => self::DISPLAY_TYPE_DROPDOWN,
    ];

    private static $variationIds;
    private static $valueIds;
    private static $parentPrices = [];

    protected $mapperConfig = [
        "table" => "products_properties_index",
        "query" => 'SELECT properties_id, products_id, properties_sort_order FROM products_properties_index WHERE products_id=[[products_id]] GROUP BY properties_id, products_id, properties_sort_order',
        "where" => "products_properties_combis_id",
        "getMethod" => "getVariations",
        "mapPull" => [
            "id" => "properties_id",
            "productId" => "products_id",
            "sort" => "properties_sort_order",
            "type" => null,
            "i18ns" => "ProductVariationI18n|addI18n",
            "values" => "ProductVariationValue|addValue",
        ],
    ];

    public function pull($data = null, $limit = null): array
    {
        return AbstractMapper::pull($data, $limit);
    }

    /**
     * @param $data
     * @return mixed|string
     */
    public function type($data)
    {
        $type = $this->db->query(sprintf("SELECT display_type FROM properties WHERE properties_id = %s", $data['properties_id']));
        return self::mapDisplayType($type[0]['display_type'] ?? 'Dropdown');
    }

    /**
     * @param $parent
     * @param $dbObj
     * @return array|array[]|\jtl\Connector\Model\DataModel|\jtl\Connector\Model\DataModel[]|mixed
     * @throws \jtl\Connector\Core\Exception\LanguageException
     */
    public function push($parent, $dbObj = null)
    {
        if (count($parent->getVariations()) > 0) {
            $masterId = $parent->getMasterProductId()->getEndpoint();
            $productId = $parent->getId()->getEndpoint();

            // old variations
            if ($parent->getIsMasterProduct() === true) {

                $_SESSION[Connector::FINISH_TASK_CLEANUP_PRODUCT_PROPERTIES] = true;
                $this->db->query('DELETE FROM products_properties_admin_select WHERE products_id=' . $productId);

                foreach ($parent->getVariations() as $variation) {
                    // get variation name in default language
                    foreach ($variation->getI18ns() as $i18n) {
                        if ($i18n->getLanguageISO() == $this->fullLocale($this->shopConfig['settings']['DEFAULT_LANGUAGE'])) {
                            $variationName = $i18n->getName();
                            break;
                        }
                    }

                    $displayType = ProductVariation::mapVariationType($variation->getType());

                    $propertiesAdminName = $this->createPropertyAdminName($variationName, $displayType);

                    // try to find existing variation id
                    $variationIdQuery = $this->db->query(sprintf('SELECT properties_id FROM properties_description WHERE properties_admin_name = "%s" ORDER BY properties_id ASC', $propertiesAdminName));
                    if (count($variationIdQuery) === 0) {
                        $variationIdQuery = $this->db->query(sprintf('SELECT properties_id FROM properties_description WHERE properties_name = "%s" ORDER BY properties_id ASC', $variationName));
                    }

                    $property = new \stdClass();
                    $property->display_type = $displayType;

                    // use existing id or generate next available one
                    if (count($variationIdQuery) > 0) {
                        $variationId = $variationIdQuery[0]['properties_id'];
                        $this->db->updateRow($property, 'properties', 'properties_id', $variationId);
                    } else {
                        $property->sort_order = $variation->getSort();
                        $variationId = $this->db->insertRow($property, 'properties');
                        $variationId = $variationId->getKey();
                    }

                    $propertiesDescription = new \stdClass();
                    $this->db->deleteRow($propertiesDescription, 'properties_description', 'properties_id', $variationId);

                    // insert/update variation
                    foreach ($variation->getI18ns() as $i18n) {
                        $varObj = new \stdClass();
                        $varObj->properties_id = $variationId;
                        $varObj->language_id = $this->locale2id($i18n->getLanguageISO());
                        $varObj->properties_name = $i18n->getName();
                        $varObj->properties_admin_name = $propertiesAdminName;

                        $this->db->insertRow($varObj, 'properties_description');
                    }

                    // VariationValues
                    $newVariationValues = [];
                    foreach ($variation->getValues() as $value) {
                        // get value name in default language
                        foreach ($value->getI18ns() as $i18n) {
                            if ($i18n->getLanguageISO() == $this->fullLocale($this->shopConfig['settings']['DEFAULT_LANGUAGE'])) {
                                $valueName = $i18n->getName();
                                $langId = $this->locale2id($i18n->getLanguageISO());
                                break;
                            }
                        }

                        $sql = 'SELECT v.properties_values_id ' . "\n" .
                            'FROM properties_values_description d ' . "\n" .
                            'LEFT JOIN properties_values v ON v.properties_values_id = d.properties_values_id ' . "\n" .
                            'LEFT JOIN properties p ON p.properties_id = v.properties_id ' . "\n" .
                            'WHERE p.properties_id = ' . $variationId . ' AND d.language_id = ' . $langId . ' AND d.values_name = "' . $valueName . '"';

                        // try to find existing value id
                        $valueIdQuery = $this->db->query($sql);

                        // use existing id or generate next available one`
                        if (count($valueIdQuery) > 0) {
                            $valueId = $valueIdQuery[0]['properties_values_id'];
                        } else {
                            $newVal = new \stdClass();
                            $newVal->properties_id = $variationId;
                            $newVal->sort_order = $variation->getSort();
                            $newVal->value_model = $value->getSku();
                            $newVal->value_price = 0;

                            $valueId = $this->db->insertRow($newVal, 'properties_values');
                            $valueId = $valueId->getKey();
                        }
                        $newVariationValues[] = $valueId;

                        // insert/update values
                        foreach ($value->getI18ns() as $i18n) {
                            $valueObj = new \stdClass();
                            $valueObj->properties_values_id = $valueId;
                            $valueObj->language_id = $this->locale2id($i18n->getLanguageISO());
                            $valueObj->values_name = $i18n->getName();

                            $this->db->deleteInsertRow(
                                $valueObj,
                                'properties_values_description',
                                ['properties_values_id', 'language_id'],
                                [$valueId, $valueObj->language_id]
                            );
                        }
                    }
                }

                if (!empty($productId) && !empty($newVariationValues)) {
                    try {
                        $this->db->DB()->begin_transaction();
                        $sql =
                            'SELECT ppcv.*' . "\n" .
                            'FROM products_properties_combis_values AS ppcv' . "\n" .
                            'LEFT JOIN products_properties_combis AS ppc ON ppc.products_properties_combis_id = ppcv.products_properties_combis_id' . "\n" .
                            'LEFT JOIN properties_values AS pv ON pv.properties_values_id = ppcv.properties_values_id' . "\n" .
                            'WHERE pv.properties_values_id NOT IN (%s) AND ppc.products_id = %s';

                        $unusedProductPropertiesValues = $this->db->query(sprintf($sql, implode(',', $newVariationValues), $productId));

                        $productPropertiesCombisValuesId = array_column($unusedProductPropertiesValues, 'products_properties_combis_values_id');

                        if (!empty($productPropertiesCombisValuesId)) {
                            $sql = 'DELETE FROM products_properties_combis_values WHERE products_properties_combis_values_id IN (%s)';
                            $this->db->query(sprintf($sql, implode(',', $productPropertiesCombisValuesId)));
                        }

                        $this->db->commit();
                    } catch (\Exception $e) {
                        Logger::write(ExceptionFormatter::format($e), Logger::ERROR, 'controller');
                        $this->db->rollback();
                    }
                }

                foreach ($parent->getPrices() as $price) {
                    if (is_null($price->getCustomerGroupId()->getEndpoint()) || $price->getCustomerGroupId()->getEndpoint() == '') {
                        $priceItem = $price->getItems()[0];
                        static::$parentPrices[$parent->getId()->getHost()] = $priceItem->getNetPrice();
                        break;
                    }
                }
            } // varcombi child
            else {
                $combi = new \stdClass();

                $id = $parent->getId()->getEndpoint();
                $combiId = explode('_', $id);

                if (!empty($id)) {
                    $combi->products_properties_combis_id = $combiId[1];
                }
                $combi->products_id = $parent->getMasterProductId()->getEndpoint();
                $combi->sort_order = $parent->getSort();
                $combi->combi_model = $parent->getSku();
                $combi->combi_ean = $parent->getEan();
                $combi->combi_quantity = $parent->getStockLevel()->getStockLevel();
                $combi->combi_shipping_status_id = $this->getShippingtime($parent);
                $combi->combi_weight = $parent->getProductWeight();
                $combi->combi_price_type = 'fix';
                $combi->combi_price = 0.;
                $combi->vpe_value = $this->products_vpe_value($parent);
                $combi->products_vpe_id = $this->products_vpe($parent);
                foreach ($parent->getPrices() as $price) {
                    if (is_null($price->getCustomerGroupId()->getEndpoint()) || $price->getCustomerGroupId()->getEndpoint() == '') {
                        $childPrice = $price->getItems()[0]->getNetPrice();
                        if (is_null(static::$parentPrices[$parent->getMasterProductId()->getHost()])) {
                            $parentObj = $this->db->query('SELECT products_price FROM products WHERE products_id="' . $combi->products_id . '"');
                            static::$parentPrices[$parent->getMasterProductId()->getHost()] = (float)$parentObj[0]['products_price'];
                        }

                        $parentPrice = static::$parentPrices[$parent->getMasterProductId()->getHost()];
                        if ($childPrice !== $parentPrice) {
                            $combi->combi_price = ($childPrice - $parentPrice);
                        }

                        break;
                    }
                }

                if (isset($combi->products_properties_combis_id)) {
                    $result = $this->db->updateRow(
                        $combi,
                        'products_properties_combis',
                        'products_properties_combis_id',
                        $combi->products_properties_combis_id
                    );
                } else {
                    $result = $this->db->insertRow($combi, 'products_properties_combis');
                }

                $combi->products_properties_combis_id = $result->getKey();

                foreach ($parent->getVariations() as $variation) {
                    $variation->getId()->setEndpoint($this->getVariationId($variation));

                    $varI18ns = [];
                    foreach ($variation->getI18ns() as $varI18n) {
                        $langId = $this->locale2id($varI18n->getLanguageISO());
                        $varI18ns[$langId] = $varI18n->getName();
                    }

                    foreach ($variation->getValues() as $value) {
                        $property = new \stdClass();
                        $property->products_id = $combi->products_id;
                        $property->properties_id = $variation->getId()->getEndpoint();
                        $property->properties_values_id = $this->getValueId($value, $variation);

                        $this->db->insertRow($property, 'products_properties_admin_select');

                        foreach ($value->getI18ns() as $i18n) {
                            $index = new \stdClass();
                            $index->products_id = $combi->products_id;
                            $index->language_id = $this->locale2id($i18n->getLanguageISO());
                            $index->properties_id = $variation->getId()->getEndpoint();
                            $index->products_properties_combis_id = $combi->products_properties_combis_id;
                            $index->properties_values_id = $this->getValueId($value, $variation);
                            $index->properties_name = $varI18ns[$index->language_id];
                            $index->properties_sort_order = $variation->getSort();
                            $index->values_name = $i18n->getName();
                            $index->value_sort_order = $value->getSort();

                            $this->db->deleteInsertRow(
                                $index,
                                'products_properties_index',
                                ['products_properties_combis_id', 'properties_values_id', 'language_id'],
                                [
                                    $index->products_properties_combis_id,
                                    $index->properties_values_id,
                                    $index->language_id,
                                ]
                            );
                        }

                        $combiValue = new \stdClass();
                        $combiValue->products_properties_combis_id = $combi->products_properties_combis_id;
                        $combiValue->properties_values_id = $this->getValueId($value, $variation);

                        $this->db->deleteInsertRow(
                            $combiValue,
                            'products_properties_combis_values',
                            ['products_properties_combis_id', 'properties_values_id'],
                            [$combiValue->products_properties_combis_id, $combiValue->properties_values_id]
                        );
                    }
                }

                $combiId = new \StdClass();
                $combiId->endpoint_id = $parent->getMasterProductId()->getEndpoint() . '_' . $result->getKey();
                $combiId->host_id = $parent->getId()->getHost();
                $this->db->deleteInsertRow(
                    $combiId,
                    'jtl_connector_link_product',
                    'endpoint_id',
                    $combiId->endpoint_id
                );
            }
        }

        return $parent->getVariations();
    }

    /**
     * @param string $variationName
     * @param string $displayType
     * @return string
     */
    protected function createPropertyAdminName(string $variationName, string $displayType): string
    {
        return sprintf('%s - %s', $variationName, $displayType);
    }

    /**
     * @param string $jtlVariationType
     * @return string
     */
    protected static function mapVariationType(string $jtlVariationType): string
    {
        return self::$variationTypeMapping[$jtlVariationType] ?? self::DISPLAY_TYPE_DROPDOWN;
    }

    /**
     * @param string $displayType
     * @return string
     */
    protected static function mapDisplayType(string $displayType): string
    {
        $key = array_search($displayType, self::$variationTypeMapping);
        return $key !== false ? $key : ProductVariationModel::TYPE_SELECT;
    }

    private function getVariationId($variation)
    {
        $variationId = false;
        if (isset(static::$variationIds[$variation->getId()->getHost()])) {
            $variationId = static::$variationIds[$variation->getId()->getHost()];
        } else {
            foreach ($variation->getI18ns() as $i18n) {
                if ($i18n->getLanguageISO() == $this->fullLocale($this->shopConfig['settings']['DEFAULT_LANGUAGE'])) {
                    $variationName = $i18n->getName();
                }
            }

            $propertyAdminName = $this->createPropertyAdminName($variationName, ProductVariation::mapVariationType($variation->getType()));

            $variationIdQuery = $this->db->query(sprintf('SELECT properties_id FROM properties_description WHERE properties_admin_name = "%s" ORDER BY properties_id ASC', $propertyAdminName));
            if (count($variationIdQuery) === 0) {
                $variationIdQuery = $this->db->query(sprintf('SELECT properties_id FROM properties_description WHERE properties_name="%s" ORDER BY properties_id ASC', $variationName));
            }

            if (count($variationIdQuery) > 0) {
                static::$variationIds[$variation->getId()->getHost()] = $variationIdQuery[0]['properties_id'];

                $variationId = $variationIdQuery[0]['properties_id'];
            }
        }

        return $variationId;
    }

    private function getValueId($value, $variation)
    {
        if (isset(static::$valueIds[$value->getId()->getHost()])) {
            return static::$valueIds[$value->getId()->getHost()];
        } else {
            foreach ($value->getI18ns() as $i18n) {
                if ($i18n->getLanguageISO() == $this->fullLocale($this->shopConfig['settings']['DEFAULT_LANGUAGE'])) {
                    $valueName = $i18n->getName();
                    $langId = $this->locale2id($i18n->getLanguageISO());
                    break;
                }
            }

            $valueIdQuery = $this->db->query('SELECT v.properties_values_id
                FROM properties_values_description d
                LEFT JOIN properties_values v ON v.properties_values_id=d.properties_values_id
                LEFT JOIN properties p ON p.properties_id=v.properties_id
                WHERE p.properties_id=' . $this->getVariationId($variation) . ' && d.language_id=' . $langId . ' && d.values_name="' . $valueName . '"');

            if (count($valueIdQuery) > 0) {
                static::$valueIds[$value->getId()->getHost()] = $valueIdQuery[0]['properties_values_id'];

                return $valueIdQuery[0]['properties_values_id'];
            }
        }

        return false;
    }

    private function getVpe($data)
    {
        foreach ($data->getI18ns() as $i18n) {
            $name = $i18n->getUnitName();

            if (!empty($name)) {
                $language_id = $this->locale2id($i18n->getLanguageISO());
                $dbResult = $this->db->query('SELECT code FROM languages WHERE languages_id=' . $language_id);

                if ($dbResult[0]['code'] == $this->shopConfig['settings']['DEFAULT_LANGUAGE']) {
                    $sql = $this->db->query('SELECT products_vpe_id FROM products_vpe WHERE language_id=' . $language_id . ' && products_vpe_name="' . $name . '"');
                    if (count($sql) > 0) {
                        return $sql[0]['products_vpe_id'];
                    }
                }
            }
        }

        return 0;
    }

    private function getShippingtime($data)
    {
        foreach ($data->getI18ns() as $i18n) {
            $name = $i18n->getDeliveryStatus();

            if (!empty($name)) {
                $language_id = $this->locale2id($i18n->getLanguageISO());
                $dbResult = $this->db->query('SELECT code FROM languages WHERE languages_id=' . $language_id);

                if ($dbResult[0]['code'] == $this->shopConfig['settings']['DEFAULT_LANGUAGE']) {
                    $sql = $this->db->query('SELECT shipping_status_id FROM shipping_status WHERE language_id=' . $language_id . ' && shipping_status_name="' . $name . '"');
                    if (count($sql) > 0) {
                        return $sql[0]['shipping_status_id'];
                    } else {
                        $nextId = $this->db->query('SELECT max(shipping_status_id) + 1 AS nextID FROM shipping_status');
                        $id = is_null($nextId[0]['nextID']) || $nextId[0]['nextID'] === 0 ? 1 : $nextId[0]['nextID'];

                        foreach ($data->getI18ns() as $i18n) {
                            $status = new \stdClass();
                            $status->shipping_status_id = $id;
                            $status->language_id = $this->locale2id($i18n->getLanguageISO());
                            $status->shipping_status_name = $i18n->getDeliveryStatus();

                            $this->db->deleteInsertRow(
                                $status,
                                'shipping_status',
                                ['shipping_status_id', 'language_id'],
                                [$status->shipping_status_id, $status->language_id]
                            );
                        }

                        return $id;
                    }
                }
            }
        }

        return 0;
    }
}
