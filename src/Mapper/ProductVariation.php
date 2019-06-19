<?php

namespace jtl\Connector\Gambio\Mapper;

use \jtl\Connector\Gambio\Mapper\BaseMapper;
use \jtl\Connector\Linker\ChecksumLinker;
use \jtl\Connector\Core\Logger\Logger;

class ProductVariation extends Product
{
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
            "i18ns" => "ProductVariationI18n|addI18n",
            "values" => "ProductVariationValue|addValue",
        ],
    ];

    public function pull($data = null, $limit = null)
    {
        $checkCombi = $this->db->query('SELECT products_properties_combis_id FROM products_properties_index WHERE products_id=' . $data['products_id']);

        if (count($checkCombi) == 0) {
            $this->mapperConfig = [
                "table" => "products_options",
                "query" => 'SELECT * FROM products_attributes WHERE products_id=[[products_id]] GROUP BY options_id',
                "where" => "options_id",
                "getMethod" => "getVariations",
                "mapPull" => [
                    "id" => "options_id",
                    "productId" => "products_id",
                    "sort" => "sort_order",
                    "i18ns" => "ProductVariationI18n|addI18n",
                    "values" => "ProductVariationValue|addValue",
                ],
            ];
        }

        return BaseMapper::pull($data, $limit);
    }

    public function push($parent, $dbObj = null)
    {
        if (count($parent->getVariations()) > 0) {
            $masterId = $parent->getMasterProductId()->getEndpoint();

            // old variations
            if (empty($masterId) && $parent->getIsMasterProduct() === false) {
                $totalStock = 0;

                // clear existing product variations
                $this->db->query('DELETE FROM products_attributes WHERE products_id=' . $parent->getId()->getEndpoint());

                foreach ($parent->getVariations() as $variation) {
                    // get variation name in default language
                    foreach ($variation->getI18ns() as $i18n) {
                        if ($i18n->getLanguageISO() == $this->fullLocale($this->shopConfig['settings']['DEFAULT_LANGUAGE'])) {
                            $varName = $i18n->getName();
                        }
                    }

                    // try to find existing variation id
                    $variationIdQuery = $this->db->query('SELECT products_options_id FROM products_options WHERE products_options_name="' . $varName . '"');

                    // use existing id or generate next available one
                    if (count($variationIdQuery) > 0) {
                        $variationId = $variationIdQuery[0]['products_options_id'];
                    } else {
                        $nextId = $this->db->query('SELECT max(products_options_id) + 1 AS nextID FROM products_options');
                        $variationId = is_null($nextId[0]['nextID']) || $nextId[0]['nextID'] === 0 ? 1 : $nextId[0]['nextID'];
                    }

                    // insert/update variation
                    foreach ($variation->getI18ns() as $i18n) {
                        $varObj = new \stdClass();
                        $varObj->products_options_id = $variationId;
                        $varObj->language_id = $this->locale2id($i18n->getLanguageISO());
                        $varObj->products_options_name = $i18n->getName();

                        $this->db->deleteInsertRow($varObj, 'products_options', ['products_options_id', 'language_id'],
                            [$variationId, $varObj->language_id]);
                    }

                    // VariationValues
                    foreach ($variation->getValues() as $value) {
                        // get value name in default language
                        foreach ($value->getI18ns() as $i18n) {
                            if ($i18n->getLanguageISO() == $this->fullLocale($this->shopConfig['settings']['DEFAULT_LANGUAGE'])) {
                                $valueName = $i18n->getName();
                            }
                        }

                        // try to find existing value id
                        $valueIdQuery = $this->db->query('SELECT v2.products_options_values_id FROM products_options_values_to_products_options v1 LEFT JOIN products_options_values v2 ON v1.products_options_values_id=v2.products_options_values_id WHERE v1.products_options_id=' . $variationId . ' && v2.products_options_values_name="' . $valueName . '"');

                        // use existing id or generate next available one
                        if (count($valueIdQuery) > 0) {
                            $valueId = $valueIdQuery[0]['products_options_values_id'];
                        } else {
                            $nextId = $this->db->query('SELECT max(products_options_values_id) + 1 AS nextID FROM products_options_values');
                            $valueId = is_null($nextId[0]['nextID']) || $nextId[0]['nextID'] === 0 ? 1 : $nextId[0]['nextID'];
                        }

                        // insert/update values
                        foreach ($value->getI18ns() as $i18n) {
                            $valueObj = new \stdClass();
                            $valueObj->products_options_values_id = $valueId;
                            $valueObj->language_id = $this->locale2id($i18n->getLanguageISO());
                            $valueObj->products_options_values_name = $i18n->getName();

                            $this->db->deleteInsertRow($valueObj, 'products_options_values',
                                ['products_options_values_id', 'language_id'], [$valueId, $valueObj->language_id]);
                        }

                        // insert/update values to variation mapping
                        $val2varObj = new \stdClass();
                        $val2varObj->products_options_id = $variationId;
                        $val2varObj->products_options_values_id = $valueId;

                        $this->db->deleteInsertRow($val2varObj, 'products_options_values_to_products_options',
                            ['products_options_id', 'products_options_values_id'], [$variationId, $valueId]);

                        // insert/update product variation
                        $pVarObj = new \stdClass();
                        $pVarObj->products_id = $parent->getId()->getEndpoint();
                        $pVarObj->options_id = $variationId;
                        $pVarObj->options_values_id = $valueId;
                        $pVarObj->attributes_stock = round($value->getStockLevel());
                        $pVarObj->options_values_weight = abs($value->getExtraWeight());
                        $pVarObj->weight_prefix = $value->getExtraWeight() < 0 ? '-' : '+';
                        $pVarObj->sortorder = $value->getSort();
                        $pVarObj->attributes_model = $value->getSku();

                        $totalStock += $pVarObj->attributes_stock;

                        // get product variation price for default customer group
                        foreach ($value->getExtraCharges() as $extraCharge) {
                            if ($extraCharge->getCustomerGroupId()->getEndpoint() == '') {
                                $pVarObj->price_prefix = $extraCharge->getExtraChargeNet() < 0 ? '-' : '+';
                                $pVarObj->options_values_price = abs($extraCharge->getExtraChargeNet());
                            }
                        }

                        $this->db->insertRow($pVarObj, 'products_attributes');
                    }
                }

                if ($parent->getStockLevel()->getStockLevel() == 0) {
                    $this->db->query('UPDATE products SET products_quantity=' . $totalStock . ' WHERE products_id=' . $parent->getId()->getEndpoint());
                }

                $this->clearUnusedVariations();
            } // varcombi master
            elseif ($parent->getIsMasterProduct() === true) {
                $checksum = ChecksumLinker::find($parent, 1);

                if ($checksum === null || $checksum->hasChanged() === true) {
                    $this->db->query('DELETE FROM products_attributes WHERE products_id=' . $parent->getId()->getEndpoint());

                    foreach ($parent->getVariations() as $variation) {
                        // get variation name in default language
                        foreach ($variation->getI18ns() as $i18n) {
                            if ($i18n->getLanguageISO() == $this->fullLocale($this->shopConfig['settings']['DEFAULT_LANGUAGE'])) {
                                $varName = $i18n->getName();
                            }
                        }

                        // try to find existing variation id
                        $variationIdQuery = $this->db->query('SELECT properties_id FROM properties_description WHERE properties_name="' . $varName . '"');

                        // use existing id or generate next available one
                        if (count($variationIdQuery) > 0) {
                            $variationId = $variationIdQuery[0]['properties_id'];
                        } else {
                            $newProp = new \stdClass();
                            $newProp->sort_order = $variation->getSort();

                            $variationId = $this->db->insertRow($newProp, 'properties');
                            $variationId = $variationId->getKey();
                        }

                        // insert/update variation
                        foreach ($variation->getI18ns() as $i18n) {
                            $varObj = new \stdClass();
                            $varObj->properties_id = $variationId;
                            $varObj->language_id = $this->locale2id($i18n->getLanguageISO());
                            $varObj->properties_name = $i18n->getName();

                            $this->db->deleteInsertRow($varObj, 'properties_description',
                                ['properties_id', 'language_id'], [$variationId, $varObj->language_id]);
                        }

                        // VariationValues
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
                                   'LEFT JOIN properties_values v ON v.properties_values_id=d.properties_values_id ' . "\n" .
                                   'LEFT JOIN properties p ON p.properties_id=v.properties_id ' . "\n" .
                                   'WHERE p.properties_id=' . $variationId . ' && d.language_id=' . $langId . ' && d.values_name="' . $valueName . '"';

                            // try to find existing value id
                            $valueIdQuery = $this->db->query($sql);

                            // use existing id or generate next available one
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

                            // insert/update values
                            foreach ($value->getI18ns() as $i18n) {
                                $valueObj = new \stdClass();
                                $valueObj->properties_values_id = $valueId;
                                $valueObj->language_id = $this->locale2id($i18n->getLanguageISO());
                                $valueObj->values_name = $i18n->getName();

                                $this->db->deleteInsertRow($valueObj, 'properties_values_description',
                                    ['properties_values_id', 'language_id'], [$valueId, $valueObj->language_id]);
                            }
                        }
                    }

                    foreach ($parent->getPrices() as $price) {
                        if (is_null($price->getCustomerGroupId()->getEndpoint()) || $price->getCustomerGroupId()->getEndpoint() == '') {
                            $priceItem = $price->getItems()[0];
                            static::$parentPrices[$parent->getId()->getHost()] = $priceItem->getNetPrice();
                            break;
                        }
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
                $combi->combi_price_type = 'calc';
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
                        if($childPrice !== $parentPrice) {
                            $combi->combi_price = ($childPrice - $parentPrice);
                        }

                        break;
                    }
                }

                if (isset($combi->products_properties_combis_id)) {
                    $result = $this->db->updateRow($combi, 'products_properties_combis',
                        'products_properties_combis_id', $combi->products_properties_combis_id);
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
    
                    $this->db->query('DELETE FROM products_properties_admin_select WHERE products_id=' . $id);
                    
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

                            $this->db->deleteInsertRow($index, 'products_properties_index',
                                ['products_properties_combis_id', 'properties_values_id', 'language_id'], [
                                    $index->products_properties_combis_id,
                                    $index->properties_values_id,
                                    $index->language_id,
                                ]);
                        }

                        $combiValue = new \stdClass();
                        $combiValue->products_properties_combis_id = $combi->products_properties_combis_id;
                        $combiValue->properties_values_id = $this->getValueId($value, $variation);

                        $this->db->deleteInsertRow($combiValue, 'products_properties_combis_values',
                            ['products_properties_combis_id', 'properties_values_id'],
                            [$combiValue->products_properties_combis_id, $combiValue->properties_values_id]);
                    }
                }

                $combiId = new \StdClass();
                $combiId->endpoint_id = $parent->getMasterProductId()->getEndpoint() . '_' . $result->getKey();
                $combiId->host_id = $parent->getId()->getHost();
                $this->db->deleteInsertRow($combiId, 'jtl_connector_link_product', 'endpoint_id',
                    $combiId->endpoint_id);
            }
        }

        return $parent->getVariations();
    }

    private function getVariationId($variation)
    {
        if (isset(static::$variationIds[$variation->getId()->getHost()])) {
            return static::$variationIds[$variation->getId()->getHost()];
        } else {
            foreach ($variation->getI18ns() as $i18n) {
                if ($i18n->getLanguageISO() == $this->fullLocale($this->shopConfig['settings']['DEFAULT_LANGUAGE'])) {
                    $varName = $i18n->getName();
                }
            }

            $variationIdQuery = $this->db->query('SELECT properties_id FROM properties_description WHERE properties_name="' . $varName . '"');

            if (count($variationIdQuery) > 0) {
                static::$variationIds[$variation->getId()->getHost()] = $variationIdQuery[0]['properties_id'];

                return $variationIdQuery[0]['properties_id'];
            }
        }

        return false;
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

                            $this->db->deleteInsertRow($status, 'shipping_status',
                                ['shipping_status_id', 'language_id'],
                                [$status->shipping_status_id, $status->language_id]);
                        }

                        return $id;
                    }
                }
            }
        }

        return 0;
    }

    private function clearUnusedVariations()
    {
        $this->db->query('
            DELETE FROM products_options_values
            WHERE products_options_values_id IN (
                SELECT * FROM (
                    SELECT v.products_options_values_id
                    FROM products_options_values v
                    LEFT JOIN products_attributes a ON v.products_options_values_id = a.options_values_id
                    WHERE a.products_attributes_id IS NULL
                    GROUP BY v.products_options_values_id
                ) relations
            )
        ');

        $this->db->query('
            DELETE FROM products_options
            WHERE products_options_id IN (
                SELECT * FROM (
                    SELECT o.products_options_id
                    FROM products_options o
                    LEFT JOIN products_attributes a ON o.products_options_id = a.options_id
                    WHERE a.products_attributes_id IS NULL
                    GROUP BY o.products_options_id
                ) relations
            )
        ');
    }
}
