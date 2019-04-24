<?php

namespace jtl\Connector\Gambio\Mapper;

use \jtl\Connector\Gambio\Mapper\BaseMapper;
use jtl\Connector\Model\Identity;
use \jtl\Connector\Model\ProductStockLevel;
use \jtl\Connector\Model\Product as ProductModel;
use \jtl\Connector\Model\ProductStockLevel as ProductStockLevelModel;
use \jtl\Connector\Type\ProductStockLevel as ProductStockLevelType;
use \jtl\Connector\Model\ProductPrice as ProductPriceModel;
use \jtl\Connector\Model\ProductPriceItem as ProductPriceItemModel;
use \jtl\Connector\Model\ProductVariation as ProductVariationModel;
use \jtl\Connector\Model\ProductVariationI18n as ProductVariationI18nModel;
use \jtl\Connector\Model\ProductVariationValue as ProductVariationValueModel;
use \jtl\Connector\Model\ProductVariationValueI18n as ProductVariationValueI18nModel;
use \jtl\Connector\Model\ProductI18n as ProductI18nModel;
use jtl\Connector\Gambio\Util\MeasurementUnitHelper;

class Product extends BaseMapper
{
    private static $idCache = [];
    
    protected $mapperConfig = [
        "table"    => "products",
        /*
        "query" => "SELECT p.*, q.quantity_unit_id, c.code_isbn, c.code_mpn, c.code_upc, c.google_export_condition, c.google_export_availability_id, g.google_category
            FROM products p
            LEFT JOIN products_quantity_unit q ON q.products_id = p.products_id
            LEFT JOIN products_item_codes c ON c.products_id = p.products_id
            LEFT JOIN products_google_categories g ON g.products_id = p.products_id
            LEFT JOIN jtl_connector_link l ON CONVERT(p.products_id, CHAR(16)) = l.endpointId COLLATE utf8_unicode_ci AND l.type = 64
            WHERE l.hostId IS NULL",
        */
        "where"    => "products_id",
        "identity" => "getId",
        "mapPull"  => [
            "id"                     => "products_id",
            "ean"                    => "products_ean",
            "stockLevel"             => "ProductStockLevel|setStockLevel",
            "sku"                    => "products_model",
            "sort"                   => "products_sort",
            "creationDate"           => "products_date_added",
            "availableFrom"          => "products_date_available",
            "productWeight"          => "products_weight",
            "manufacturerId"         => null,
            "unitId"                 => null,
            "basePriceDivisor"       => "products_vpe_value",
            "considerBasePrice"      => null,
            "isActive"               => null,
            "isTopProduct"           => "products_startpage",
            "considerStock"          => null,
            "considerVariationStock" => null,
            "permitNegativeStock"    => null,
            "i18ns"                  => "ProductI18n|addI18n",
            "categories"             => "Product2Category|addCategory",
            "prices"                 => "ProductPrice|addPrice",
            "specialPrices"          => "ProductSpecialPrice|addSpecialPrice",
            "variations"             => "ProductVariation|addVariation",
            "invisibilities"         => "ProductInvisibility|addInvisibility",
            "attributes"             => "ProductAttr|addAttribute",
            "vat"                    => null,
            "isMasterProduct"        => null,
            "measurementUnitId"      => null,
            "isbn"                   => "code_isbn",
            "manufacturerNumber"     => "code_mpn",
            "upc"                    => "code_upc",
            "minimumOrderQuantity"   => "gm_min_order",
            "packagingQuantity"      => "gm_graduated_qty",
        ],
        "mapPush"  => [
            "products_id"                              => "id",
            "products_ean"                             => "ean",
            "products_quantity"                        => null,
            "products_model"                           => "sku",
            "products_sort"                            => "sort",
            "products_date_added"                      => "creationDate",
            "products_date_available"                  => "availableFrom",
            "products_weight"                          => "productWeight",
            "manufacturers_id"                         => null,
            "products_vpe"                             => null,
            "products_vpe_value"                       => null,
            "products_vpe_status"                      => null,
            "products_status"                          => "isActive",
            "products_startpage"                       => "isTopProduct",
            "products_tax_class_id"                    => null,
            "Product2Category|addCategory"             => "categories",
            "ProductPrice|addPrice"                    => "prices",
            "ProductSpecialPrice|addSpecialPrice"      => "specialPrices",
            "ProductVariation|addVariation"            => "variations",
            "ProductInvisibility|addInvisibility|true" => "invisibilities",
            //"ProductAttr|addAttribute|true" => "attributes",
            "ProductI18n|addI18n"                      => "i18ns",
            "products_image"                           => null,
            "products_shippingtime"                    => null,
            "gm_min_order"                             => null,
            "gm_graduated_qty"                         => null,
            "gm_show_date_added"                       => null,
        ],
    ];
    
    protected static $specialAttributes = [
        'products_status'         => 'Aktiv',
        'gm_price_status'         => 'Preis-Status',
        'gm_show_qty_info'        => 'Lagerbestand anzeigen',
        'gm_show_weight'          => 'Gewicht anzeigen',
        'products_fsk18'          => 'FSK 18',
        'product_template'        => 'Produkt Vorlage',
        'options_template'        => 'Optionen Vorlage',
        'products_startpage_sort' => 'Sortierreihenfolge auf Startseite',
        'nc_ultra_shipping_costs'  => 'Versandkosten',
        'gm_show_price_offer'     => 'Woanders günstiger?-Modul anzeigen',
        'gm_options_template'     => 'Vorlage für Artikelattribute in Übersicht',
        'gm_priority'            => 'Priorität in Sitemap',
        'gm_changefreq'           => 'Änderungsfrequenz in Sitemap',
        'gm_sitemap_entry'        => 'In Sitemap aufnehmen',
        'product_type'            => 'Artikeltyp',
    ];
    
    public function pull($data = null, $limit = null)
    {
        $this->mapperConfig['query'] =
            'SELECT j.* , qud.unit_name, pv.products_vpe_name vpe_name, c.code_isbn, c.code_mpn, c.code_upc, c.google_export_condition, c.google_export_availability_id, g.google_category ' . "\n" .
            'FROM (' . "\n" .
            '  SELECT p.* ' . "\n" .
            '  FROM products p ' . "\n" .
            '  LEFT JOIN jtl_connector_link_product l ON CONVERT( p.products_id, CHAR( 16 ) ) = l.endpoint_id ' . "\n" .
            '  COLLATE utf8_unicode_ci ' . "\n" .
            '  WHERE l.host_id IS NULL ' . "\n" .
            '  LIMIT ' . $limit . "\n" .
            ') AS j ' . "\n" .
            'LEFT JOIN products_quantity_unit pqu ON pqu.products_id = j.products_id ' . "\n" .
            'LEFT JOIN products_item_codes c ON c.products_id = j.products_id ' . "\n" .
            'LEFT JOIN products_google_categories g ON g.products_id = j.products_id ' . "\n" .
            'LEFT JOIN languages la ON la.code = \'' . $this->shopConfig['settings']['DEFAULT_LANGUAGE'] . '\' ' . "\n" .
            'LEFT JOIN quantity_unit_description qud ON pqu.quantity_unit_id = qud.quantity_unit_id AND qud.language_id = la.languages_id ' . "\n" .
            'LEFT JOIN products_vpe pv ON pv.products_vpe_id = j.products_vpe AND pv.language_id = la.languages_id';
        
        $dbResult = $this->executeQuery($data, $limit);
        
        $return = [];
        foreach ($dbResult as $data) {
            /** @var \jtl\Connector\Model\Product $product */
            $product = $this->generateModel($data);
            $this->setMeasurementsData($product, $data);
            $return[] = $product;
        }
        
        if (count($return) < $limit) {
            $limitQuery = isset($limit) ? ' LIMIT ' . $limit : '';
            
            $sql =
                'SELECT j.* , qud.unit_name, pv.products_vpe_name vpe_name ' . "\n" .
                'FROM (' . "\n" .
                '  SELECT *' . "\n" .
                '  FROM products_properties_combis c' . "\n" .
                '  LEFT JOIN products p USING (products_id)' . "\n" .
                '  LEFT JOIN jtl_connector_link_product l ON CONCAT(c.products_id,"_",c.products_properties_combis_id) = l.endpoint_id' . "\n" .
                '  WHERE l.host_id IS NULL' . $limitQuery . "\n" .
                ') AS j ' . "\n" .
                'LEFT JOIN products_quantity_unit pqu ON pqu.products_id = j.products_id ' . "\n" .
                'LEFT JOIN languages la ON la.code = \'' . $this->shopConfig['settings']['DEFAULT_LANGUAGE'] . '\' ' . "\n" .
                'LEFT JOIN quantity_unit_description qud ON pqu.quantity_unit_id = qud.quantity_unit_id AND qud.language_id = la.languages_id ' . "\n" .
                'LEFT JOIN products_vpe pv ON pv.products_vpe_id = j.products_vpe_id AND pv.language_id = la.languages_id';
            
            $combis = $this->db->query($sql);
            
            foreach ($combis as $combiData) {
                $varcombi = (new ProductModel())
                    ->setMasterProductId($this->identity($combiData['products_id']))
                    ->setId($this->identity($combiData['products_id'] . '_' . $combiData['products_properties_combis_id']))
                    ->setSku($combiData['combi_model'])
                    ->setEan($combiData['combi_ean'])
                    ->setProductWeight(floatval($combiData['combi_weight']))
                    ->setSort(intval($combiData['sort_order']))
                    ->setConsiderStock(true)
                    ->setConsiderVariationStock(true)
                    ->setIsActive(true)
                    ->setVat($this->vat($combiData));
                
                $this->setMeasurementsData($varcombi, $combiData, true);
                
                $i18nStatus = $this->db->query('SELECT * FROM shipping_status WHERE shipping_status_id=' . $combiData['combi_shipping_status_id']);
                
                foreach ($i18nStatus as $status) {
                    $i18n = new ProductI18nModel();
                    $i18n->setProductId($varcombi->getId());
                    $i18n->setDeliveryStatus($status['shipping_status_name']);
                    $i18n->setLanguageISO($this->id2locale($status['language_id']));
                    $varcombi->addI18n($i18n);
                }
                
                $stockLevel = new ProductStockLevelModel();
                $stockLevel->setProductId($varcombi->getId());
                $stockLevel->setStockLevel(floatval($combiData['combi_quantity']));
                
                $varcombi->setStockLevel($stockLevel);
                
                $default = new ProductPriceModel();
                $default->setId($this->identity($varcombi->getId()->getEndpoint() . '_default'));
                $default->setProductId($varcombi->getId());
                $default->setCustomerGroupId($this->identity(null));
                
                $defaultItem = new ProductPriceItemModel();
                $defaultItem->setProductPriceId($default->getId());
                $price = $combiData['combi_price_type'] === 'calc' ? floatval($combiData['products_price']) + floatval($combiData['combi_price']) : floatval($combiData['combi_price']);
                $defaultItem->setNetPrice($price);
                
                $default->addItem($defaultItem);
                
                $varcombi->setprices([$default]);
                
                $variationQuery = $this->db->query('SELECT * FROM products_properties_index WHERE products_properties_combis_id=' . $combiData['products_properties_combis_id']);
                
                $variations = [];
                
                foreach ($variationQuery as $variation) {
                    if (!isset($variations[$variation['properties_id']])) {
                        $variations[$variation['properties_id']] = $variation;
                    }
                    
                    $variations[$variation['properties_id']]['i18ns'][$variation['language_id']] = [$variation['properties_name'], $variation['values_name']];
                }
                
                $variationsArray = [];
                
                foreach ($variations as $variation) {
                    $varModel = new ProductVariationModel();
                    $varModel->setId($this->identity($variation['properties_id']));
                    $varModel->setProductId($varcombi->getId());
                    $varModel->setSort(intval($variation['properties_sort_order']));
                    
                    $varValueModel = new ProductVariationValueModel();
                    $varValueModel->setId($this->identity($variation['properties_values_id']));
                    $varValueModel->setProductVariationId($varModel->getId());
                    $varValueModel->setSort(intval($variation['value_sort_order']));
                    
                    $variationI18ns = [];
                    $variationValueI18ns = [];
                    
                    foreach ($variation['i18ns'] as $language => $names) {
                        $variationI18n = new ProductVariationI18nModel();
                        $variationI18n->setProductvariationId($varModel->getId());
                        $variationI18n->setLanguageISO($this->id2locale($language));
                        $variationI18n->setName($names[0]);
                        
                        $variationValueI18n = new ProductVariationValueI18nModel();
                        $variationValueI18n->setProductvariationValueId($varValueModel->getId());
                        $variationValueI18n->setLanguageISO($variationI18n->getLanguageISO());
                        $variationValueI18n->setName($names[1]);
                        
                        $variationI18ns[] = $variationI18n;
                        $variationValueI18ns[] = $variationValueI18n;
                    }
                    
                    $varModel->setI18ns($variationI18ns);
                    $varValueModel->setI18ns($variationValueI18ns);
                    
                    $varModel->setValues([$varValueModel]);
                    
                    $variationsArray[] = $varModel;
                }
                
                $varcombi->setVariations($variationsArray);
                
                $return[] = $varcombi;
            }
        }
        
        return $return;
    }
    
    /**
     * @param ProductModel $product
     * @param mixed[] $data
     * @param bool $isCombi
     */
    protected function setMeasurementsData(ProductModel $product, array $data, $isCombi = false)
    {
        $vpeName = (isset($data['vpe_name']) && !empty($data['vpe_name']) ? $data['vpe_name'] : null);
        $vpeValue = (isset($data['products_vpe_value']) && !empty($data['products_vpe_value']) ? (float)$data['products_vpe_value'] : null);
        if ($isCombi) {
            $vpeValue = (isset($data['vpe_value']) && !empty($data['vpe_value']) ? (float)$data['vpe_value'] : null);
        }
        
        if (isset($data['unit_name']) && !empty($data['unit_name'])) {
            $unitId = new Identity($data['unit_name']);
            $product->setUnitId($unitId);
            $product->setBasePriceUnitName($data['unit_name']);
        }
        
        if (!is_null($vpeName)) {
            $measurementUnit = null;
            $basePriceQuantity = 1.0;
            if (MeasurementUnitHelper::isUnit($vpeName)) {
                $measurementUnit = strtolower($vpeName);
            } elseif (MeasurementUnitHelper::isUnitByName($vpeName)) {
                $measurementUnit = MeasurementUnitHelper::getUnitCode($vpeName);
            } elseif (MeasurementUnitHelper::isCombinedUnit($vpeName)) {
                $splitted = MeasurementUnitHelper::splitCombinedUnit($vpeName);
                $measurementUnit = $splitted['unit'];
                $basePriceQuantity = $splitted['quantity'];
            }
            
            if (!is_null($measurementUnit) && MeasurementUnitHelper::isUnit($measurementUnit)) {
                $measurementUnitId = new Identity(MeasurementUnitHelper::getUnitName($measurementUnit));
                $product->setBasepriceUnitId($measurementUnitId);
                $quantity = $vpeValue * $basePriceQuantity;
                $product
                    ->setMeasurementQuantity($quantity)
                    ->setBasePriceUnitCode($measurementUnit)
                    ->setMeasurementUnitCode($measurementUnit);
                
                if ($quantity > 0) {
                    $factor = MeasurementUnitHelper::getUnitFactor($measurementUnit);
                    if ($factor !== 1 && $basePriceQuantity === 1.0) {
                        $basePriceQuantity = $this->calculateBasePriceFactor($vpeValue);
                    }
                    $product
                        ->setConsiderBasePrice(true)
                        ->setBasePriceDivisor($quantity)
                        ->setMeasurementUnitId($measurementUnitId)
                        ->setBasePriceQuantity($basePriceQuantity);
                }
            }
        }
    }
    
    /**
     * @param float $number
     * @return float
     */
    protected function calculateBasePriceFactor($number)
    {
        if (!is_numeric($number)) {
            throw new \RuntimeException($number . ' is not a number!');
        }
        
        for ($i = 1; $i < $number; $i *= 10) {
        
        }
        
        return ($i / 10.);
    }
    
    public function push($product, $dbObj = null)
    {
        if (is_null($dbObj)) {
            $dbObj = new \stdClass();
        }
        
        $masterId = $product->getMasterProductId()->getEndpoint();
        if (empty($masterId) && isset(static::$idCache[$product->getMasterProductId()->getHost()])) {
            $masterId = static::$idCache[$product->getMasterProductId()->getHost()];
            $product->getMasterProductId()->setEndpoint($masterId);
        }
        
        $isVarCombiChild = !empty($masterId);
        $id = $product->getId()->getEndpoint();
        if ($isVarCombiChild) {
            $this->mapperConfig['mapPush'] = [
                "ProductVariation|addVariation" => "variations",
            ];
        } elseif (!empty($id)) {
            foreach ($this->getCustomerGroups() as $group) {
                $this->db->query('DELETE FROM personal_offers_by_customers_status_' . $group['customers_status_id'] . ' WHERE products_id="' . $id . '"');
            }
            
            //$this->db->query('DELETE FROM specials WHERE products_id='.$id);
            $this->db->query(sprintf('
                        DELETE FROM products_attributes
                        WHERE products_id="%s" AND products_attributes_id NOT IN (
                            SELECT products_attributes_id FROM products_attributes_download
                        )'
                , $id));
        }
        
        return parent::push($product, $dbObj);
    }
    
    /**
     * @param $returnModel
     * @param $dbObj
     * @param ProductModel $product
     */
    protected function pushDone($returnModel, $dbObj, $product)
    {
        if ($product->getIsMasterProduct() === true) {
            static::$idCache[$product->getId()->getHost()] = $product->getId()->getEndpoint();
        }
        
        $productsId = $product->getId()->getEndpoint();
        if (empty($productsId) || $product->getMasterProductId()->getHost() !== 0) {
            return;
        }
        
        $checkCodes = $this->db->query('SELECT products_id FROM products_item_codes WHERE products_id="' . $product->getId()->getEndpoint() . '"');
        
        $codes = new \stdClass();
        $codes->products_id = $productsId;
        $codes->code_isbn = $product->getIsbn();
        $codes->code_upc = $product->getUpc();
        $codes->code_mpn = $product->getManufacturerNumber();
        $codes->expiration_date = $product->getMinBestBeforeDate() ? $product->getMinBestBeforeDate()->format('Y-m-d') : '0000-00-00';
        
        $dbObj->products_status = 1;
        foreach ($product->getAttributes() as $attr) {
            foreach ($attr->getI18ns() as $i18n) {
                $attributeName = trim($i18n->getName());
                $specialAttribute = array_key_exists($attributeName, self::$specialAttributes);
                if (!$specialAttribute) {
                    $result = array_search($attributeName, self::$specialAttributes);
                    if (!empty($result)) {
                        $specialAttribute = true;
                        $attributeName = trim($result);
                    }
                }
                
                if ($specialAttribute) {
                    $dbObj->$attributeName = trim($i18n->getValue());
                    break;
                } elseif ($attributeName === 'Google Zustand') {
                    $codes->google_export_condition = trim($i18n->getValue());
                } elseif ($attributeName === 'Google Verfuegbarkeit ID') {
                    $codes->google_export_availability_id = trim($i18n->getValue());
                } elseif ($attributeName === 'Wesentliche Produktmerkmale') {
                    $language_id = $this->locale2id(trim($i18n->getLanguageISO()));
                    $sql = 'INSERT INTO products_description (products_id,language_id,checkout_information) VALUES(' . $productsId . ',' . $language_id . ',"' . $this->db->escapeString($i18n->getValue()) . '") ' .
                        'ON DUPLICATE KEY UPDATE checkout_information = "' . $this->db->escapeString(trim($i18n->getValue())) . '";';
                    $this->db->query($sql);
                } elseif ($attributeName === 'Google Kategorie') {
                    $obj = new \stdClass();
                    $obj->products_id = $productsId;
                    $obj->google_category = trim($i18n->getValue());
                    $this->db->deleteInsertRow($obj, 'products_google_categories', 'products_id', $productsId);
                }
            }
        }
        
        $this->db->updateRow($dbObj, 'products', 'products_id', $productsId);
        $attributes = (new ProductAttr())->push($product);
        
        if (count($checkCodes) > 0) {
            $this->db->updateRow($codes, 'products_item_codes', 'products_id', $productsId);
        } else {
            $this->db->insertRow($codes, 'products_item_codes');
        }
        
        $this->determineQuantityUnit($product);
    }
    
    private function determineQuantityUnit($data)
    {
        $id = null;
        
        $skip = true;
        
        foreach ($data->getI18ns() as $i18n) {
            $name = $i18n->getUnitName();
            $language_id = $this->locale2id($i18n->getLanguageISO());
            
            $dbResult = $this->db->query('SELECT code FROM languages WHERE languages_id=' . $language_id);
            
            if ($dbResult[0]['code'] == $this->shopConfig['settings']['DEFAULT_LANGUAGE']) {
                $sql = $this->db->query('SELECT quantity_unit_id FROM quantity_unit_description WHERE language_id=' . $language_id . ' && unit_name="' . $name . '"');
                if (count($sql) > 0) {
                    $id = $sql[0]['quantity_unit_id'];
                }
            }
            
            if (!empty($name)) {
                $skip = false;
            }
        }
        
        if ($skip === false) {
            if (is_null($id)) {
                $newUnit = new \stdClass();
                $newUnit->quantity_unit_id = null;
                $idResult = $this->db->insertRow($newUnit, 'quantity_unit');
                $id = $idResult->getKey();
            } else {
                $this->db->query('DELETE FROM quantity_unit_description WHERE quantity_unit_id=' . $id);
            }
            
            foreach ($data->getI18ns() as $i18n) {
                $unit = new \stdClass();
                $unit->language_id = $this->locale2id($i18n->getLanguageISO());
                $unit->quantity_unit_id = $id;
                $unit->unit_name = $i18n->getUnitName();
                
                $this->db->insertRow($unit, 'quantity_unit_description');
            }
            
            $quantityProduct = new \stdClass();
            $quantityProduct->products_id = $data->getId()->getEndpoint();
            $quantityProduct->quantity_unit_id = $id;
            
            $this->db->deleteInsertRow($quantityProduct, 'products_quantity_unit', 'products_id', $quantityProduct->products_id);
        }
    }
    
    public function delete($data)
    {
        $id = $data->getId()->getEndpoint();
        
        if (strpos($id, '_') !== false) {
            if (!empty($id) && $id != '') {
                try {
                    $combiId = explode('_', $id);
                    $combiId = $combiId[1];
                    
                    $this->db->query('DELETE FROM products_properties_index WHERE products_properties_combis_id=' . $combiId);
                    $this->db->query('DELETE FROM products_properties_combis_values WHERE products_properties_combis_id=' . $combiId);
                    $this->db->query('DELETE FROM products_properties_combis WHERE products_properties_combis_id=' . $combiId);
                    
                    $this->db->query('DELETE FROM jtl_connector_link_product WHERE endpoint_id="' . $id . '"');
                } catch (\Exception $e) {
                }
            }
        } else {
            if (!empty($id) && $id != '') {
                try {
                    $this->db->query('DELETE FROM products WHERE products_id=' . $id);
                    $this->db->query('DELETE FROM products_to_categories WHERE products_id=' . $id);
                    $this->db->query('DELETE FROM products_description WHERE products_id=' . $id);
                    $this->db->query('DELETE FROM products_images WHERE products_id=' . $id);
                    $this->db->query('DELETE FROM products_attributes WHERE products_id=' . $id);
                    $this->db->query('DELETE FROM products_xsell WHERE products_id=' . $id . ' OR xsell_id=' . $id);
                    $this->db->query('DELETE FROM specials WHERE products_id=' . $id);
                    $this->db->query('DELETE FROM products_properties_index WHERE products_id=' . $id);
                    $this->db->query('DELETE v FROM products_properties_combis_values v LEFT JOIN products_properties_combis c ON c.products_properties_combis_id = v.products_properties_combis_id  WHERE c.products_id=' . $id);
                    $this->db->query('DELETE FROM products_properties_combis WHERE products_id=' . $id);
                    $this->db->query('DELETE FROM products_quantity_unit WHERE products_id=' . $id);
                    $this->db->query('DELETE FROM categories_index WHERE products_id=' . $id);
                    $this->db->query('DELETE FROM products_item_codes WHERE products_id=' . $id);
                    
                    //General product attribute values cleanings
                    $this->db->query('DELETE FROM additional_field_value_descriptions WHERE additional_field_value_id IN (SELECT `additional_field_value_id` FROM `additional_field_values` afv LEFT JOIN additional_fields af ON afv.additional_field_id = af.additional_field_id WHERE af.item_type = \'product\' AND item_id NOT IN (SELECT products_id FROM products WHERE 1))');
                    $this->db->query('DELETE afv FROM `additional_field_values` afv LEFT JOIN additional_fields af ON afv.additional_field_id = af.additional_field_id WHERE af.item_type = \'product\' AND item_id NOT IN (SELECT products_id FROM products WHERE 1)');
                    
                    foreach ($this->getCustomerGroups() as $group) {
                        $this->db->query('DELETE FROM personal_offers_by_customers_status_' . $group['customers_status_id'] . ' WHERE products_id=' . $id);
                    }
                    
                    $this->db->query('DELETE FROM jtl_connector_link_product WHERE endpoint_id="' . $id . '"');
                } catch (\Exception $e) {
                }
            }
        }
        
        return $data;
    }
    
    protected function isActive($data)
    {
        return true;
    }
    
    protected function gm_show_date_added($data)
    {
        return $data->getAvailableFrom() > new \DateTime() ? 1 : 0;
    }
    
    protected function gm_min_order($data)
    {
        return $data->getMinimumOrderQuantity() == 0 ? 1 : $data->getMinimumOrderQuantity();
    }
    
    protected function gm_graduated_qty($data)
    {
        return $data->getPackagingQuantity() == 0 ? 1 : $data->getPackagingQuantity();
    }
    
    protected function isMasterProduct($data)
    {
        $query = $this->db->query('SELECT products_properties_combis_id FROM products_properties_combis WHERE products_id=' . $data['products_id']);
        
        return count($query) === 0 ? false : true;
    }
    
    protected function considerBasePrice($data)
    {
        return $data['products_vpe_status'] == 1 ? true : false;
    }
    
    protected function products_vpe($data)
    {
        /** @var ProductModel $data */
        $name = $data->getBasePriceUnitName();
        if (MeasurementUnitHelper::isUnit($data->getBasePriceUnitName())) {
            $name = MeasurementUnitHelper::getUnitName($data->getBasePriceUnitName());
        }
        
        if ($data->getConsiderBasePrice() && !is_null($data->getBasePriceQuantity()) && $data->getBasePriceQuantity() > 1.) {
            $name = $data->getBasePriceQuantity() . $data->getBasePriceUnitName();
        }
        
        if (!empty($name)) {
            foreach ($data->getI18ns() as $i18n) {
                $language_id = $this->locale2id($i18n->getLanguageISO());
                $dbResult = $this->db->query('SELECT code FROM languages WHERE languages_id=' . $language_id);
                
                if ($dbResult[0]['code'] == $this->shopConfig['settings']['DEFAULT_LANGUAGE']) {
                    $sql = $this->db->query('SELECT products_vpe_id FROM products_vpe WHERE language_id=' . $language_id . ' && products_vpe_name="' . $name . '"');
                    if (count($sql) > 0) {
                        return $sql[0]['products_vpe_id'];
                    } else {
                        $nextId = $this->db->query('SELECT max(products_vpe_id) + 1 AS nextID FROM products_vpe');
                        $id = is_null($nextId[0]['nextID']) || $nextId[0]['nextID'] === 0 ? 1 : $nextId[0]['nextID'];
                        
                        foreach ($data->getI18ns() as $i18n) {
                            $status = new \stdClass();
                            $status->products_vpe_id = $id;
                            $status->language_id = $this->locale2id($i18n->getLanguageISO());
                            $status->products_vpe_name = $name;
                            
                            $this->db->deleteInsertRow($status, 'products_vpe', ['products_vpe_id', 'language_id'], [$status->product_vpe_id, $status->language_id]);
                        }
                        
                        return $id;
                    }
                }
            }
        }
        
        return 0;
    }
    
    protected function products_vpe_value($data)
    {
        /** @var ProductModel $data */
        $value = $data->getMeasurementQuantity();
        if ($data->getConsiderBasePrice() && $data->getBasePriceQuantity() > 0 && MeasurementUnitHelper::isUnit($data->getMeasurementUnitCode())) {
            //$value /= $data->getBasePriceQuantity();
            $value = ($data->getMeasurementQuantity() * MeasurementUnitHelper::getUnitFactor($data->getMeasurementUnitCode())) / ($data->getBasePriceQuantity() * MeasurementUnitHelper::getUnitFactor($data->getBasePriceUnitCode()));
            
        }
        
        return $value;
    }
    
    protected function products_shippingtime($data)
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
                            
                            $this->db->deleteInsertRow($status, 'shipping_status', ['shipping_status_id', 'language_id'], [$status->shipping_status_id, $status->language_id]);
                        }
                        
                        return $id;
                    }
                }
            }
        }
        
        return '';
    }
    
    protected function products_vpe_status($data)
    {
        return $data->getConsiderBasePrice() == true ? 1 : 0;
    }
    
    protected function products_image($data)
    {
        $id = $data->getId()->getEndpoint();
        
        if (!empty($id)) {
            $img = $this->db->query('SELECT products_image FROM products WHERE products_id =' . $id);
            $img = $img[0]['products_image'];
            
            if (isset($img)) {
                return $img;
            }
        }
        
        return '';
    }
    
    protected function manufacturerId($data)
    {
        return $this->replaceZero($data['manufacturers_id']);
    }
    
    protected function manufacturers_id($data)
    {
        $id = $data->getManufacturerId()->getEndpoint();
        
        if ($id !== '') {
            return $id;
        }
        
        return 0;
    }
    
    protected function unitId($data)
    {
        return !is_null($data['unit_name']) ? $data['unit_name'] : '';
    }
    
    protected function measurementUnitId($data)
    {
        if (!is_null($data['unit_name']) && MeasurementUnitHelper::isUnitByName($data['unit_name'])) {
            return $data['unit_name'];
        }
        
        return '';
    }
    
    protected function considerStock($data)
    {
        return true;
    }
    
    protected function considerVariationStock($data)
    {
        $check = $this->db->query('SELECT products_id FROM products_attributes WHERE products_id=' . $data['products_id']);
        
        return count($check) > 0 ? true : false;
    }
    
    protected function permitNegativeStock($data)
    {
        return $this->shopConfig['settings']['STOCK_ALLOW_CHECKOUT'];
    }
    
    protected function vat($data)
    {
        $sql = $this->db->query('SELECT r.tax_rate FROM zones_to_geo_zones z LEFT JOIN tax_rates r ON z.geo_zone_id=r.tax_zone_id WHERE z.zone_country_id = ' . $this->shopConfig['settings']['STORE_COUNTRY'] . ' && r.tax_class_id=' . $data['products_tax_class_id']);
        
        if (empty($sql)) {
            $sql = $this->db->query('SELECT tax_rate FROM tax_rates WHERE tax_rates_id=' . $this->connectorConfig->tax_rate);
        }
        
        return floatval($sql[0]['tax_rate']);
    }
    
    protected function products_tax_class_id($data)
    {
        $sql = $this->db->query('SELECT r.tax_class_id FROM zones_to_geo_zones z LEFT JOIN tax_rates r ON z.geo_zone_id=r.tax_zone_id WHERE z.zone_country_id = ' . $this->shopConfig['settings']['STORE_COUNTRY'] . ' && r.tax_rate=' . $data->getVat());
        
        if (empty($sql)) {
            $sql = $this->db->query('SELECT tax_class_id FROM tax_rates WHERE tax_rates_id=' . $this->connectorConfig->tax_rate);
        }
        
        return $sql[0]['tax_class_id'];
    }
    
    protected function products_quantity($data)
    {
        return round($data->getStockLevel()->getStockLevel());
    }
    
    public function statistic()
    {
        $count = 0;
        
        $products = $this->db->query('SELECT p.products_id
            FROM products p             
            LEFT JOIN jtl_connector_link_product l ON CONVERT(p.products_id, CHAR(16)) = l.endpoint_id COLLATE utf8_unicode_ci 
            WHERE l.host_id IS NULL');
        
        $combis = $this->db->query('SELECT c.products_properties_combis_id
            FROM products_properties_combis c 
            LEFT JOIN jtl_connector_link_product l ON CONCAT(c.products_id,"_",c.products_properties_combis_id) = l.endpoint_id 
            WHERE l.host_id IS NULL');
        
        $count += count($products);
        $count += count($combis);
        
        return $count;
    }
    
    /**
     * @return string[]
     */
    public static function getSpecialAttributes()
    {
        return self::$specialAttributes;
    }
}
