<?php
namespace jtl\Connector\Gambio\Mapper;

use \jtl\Connector\Gambio\Mapper\BaseMapper;
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

class Product extends BaseMapper
{
    private static $idCache = array();

    protected $mapperConfig = array(
        "table" => "products",
        "query" => "SELECT p.*, q.quantity_unit_id, c.code_isbn, c.code_mpn, c.code_upc, c.google_export_condition, c.google_export_availability_id, g.google_category
            FROM products p 
            LEFT JOIN products_quantity_unit q ON q.products_id = p.products_id
            LEFT JOIN products_item_codes c ON c.products_id = p.products_id
            LEFT JOIN products_google_categories g ON g.products_id = p.products_id
            LEFT JOIN jtl_connector_link l ON CONVERT(p.products_id, CHAR(16)) = l.endpointId COLLATE utf8_unicode_ci AND l.type = 64 
            WHERE l.hostId IS NULL",
        "where" => "products_id",
        "identity" => "getId",
        "mapPull" => array(
            "id" => "products_id",
            "ean" => "products_ean",
            "stockLevel" => "ProductStockLevel|setStockLevel",
            "sku" => "products_model",
            "sort" => "products_sort",
            "creationDate" => "products_date_added",
            "availableFrom" => "products_date_available",
            "productWeight" => "products_weight",
            "manufacturerId" => null,
            "manufacturerNumber" => "products_manufacturers_model",
            "unitId" => null,
            "basePriceDivisor" => "products_vpe_value",
            "considerBasePrice" => null,
            "isActive" => "products_status",
            "isTopProduct" => "products_startpage",
            "considerStock" => null,
            "considerVariationStock" => null,
            "permitNegativeStock" => null,
            "i18ns" => "ProductI18n|addI18n",
            "categories" => "Product2Category|addCategory",
            "prices" => "ProductPrice|addPrice",
            "specialPrices" => "ProductSpecialPrice|addSpecialPrice",
            "variations" => "ProductVariation|addVariation",
            "invisibilities" => "ProductInvisibility|addInvisibility",
            "attributes" => "ProductAttr|addAttribute",
            "varCombinations" => "ProductVarCombination|addVarCombination",
            "vat" => null,
            "isMasterProduct" => null,
            "measurementUnitCode" => "quantity_unit_id",
            "isbn" => "code_isbn",
            "manufacturerNumber" => "code_mpn",
            "upc" => "code_upc",
            "minimumOrderQuantity" => "gm_min_order",
            "packagingQuantity" => "gm_graduated_qty"
        ),
        "mapPush" => array(
            "products_id" => "id",
            "products_ean" => "ean",
            "products_quantity" => null,
            "products_model" => "sku",
            "products_sort" => "sort",
            "products_date_added" => "creationDate",
            "products_date_available" => "availableFrom",
            "products_weight" => "productWeight",
            "manufacturers_id" => "manufacturerId",
            "products_vpe" => null,
            "products_vpe_value" => "basePriceDivisor",
            "products_vpe_status" => null,
            "products_status" => "isActive",
            "products_startpage" => "isTopProduct",
            "products_tax_class_id" => null,
            "ProductI18n|addI18n" => "i18ns",
            "Product2Category|addCategory" => "categories",
            "ProductPrice|addPrice" => "prices",
            "ProductSpecialPrice|addSpecialPrice" => "specialPrices",
            "ProductVariation|addVariation" => "variations",
            "ProductInvisibility|addInvisibility|true" => "invisibilities",
            "ProductAttr|addAttribute|true" => "attributes",
            "products_image" => null,
            "products_shippingtime" => null,
            "gm_min_order" => "minimumOrderQuantity",
            "gm_graduated_qty" => "packagingQuantity"
        )
    );

    public function pull($data, $limit = null)
    {
        $return = parent::pull($data, $limit);

        $combis = $this->db->query('
            SELECT c.*,p.products_price 
            FROM products_properties_combis c 
            LEFT JOIN products p ON p.products_id=c.products_id 
            LEFT JOIN jtl_connector_link l ON CONCAT(c.products_id,"_",c.products_properties_combis_id) = l.endpointId AND l.type = 64 
            WHERE l.hostId IS NULL');

        foreach ($combis as $combi) {
            $varcombi = new ProductModel();
            $varcombi->setMasterProductId($this->identity($combi['products_id']));
            $varcombi->setId($this->identity($combi['products_id'].'_'.$combi['products_properties_combis_id']));
            $varcombi->setSku($combi['combi_model']);
            $varcombi->setEan($combi['combi_ean']);
            $varcombi->setProductWeight(floatval($combi['combi_weight']));
            $varcombi->setSort(intval($combi['sort_order']));
            $varcombi->setConsiderStock(true);
            $varcombi->setConsiderVariationStock(true);

            $i18nStatus = $this->db->query('SELECT * FROM shipping_status WHERE shipping_status_id='.$combi['combi_shipping_status_id']);

            foreach ($i18nStatus as $status) {
                $i18n = new ProductI18nModel();
                $i18n->setProductId($varcombi->getId());
                $i18n->setDeliveryStatus($status['shipping_status_name']);
                $i18n->setLanguageISO($this->id2locale($status['language_id']));
                $varcombi->addI18n($i18n);
            }

            $stockLevel = new ProductStockLevelModel();
            $stockLevel->setProductId($varcombi->getId());
            $stockLevel->setStockLevel(floatval($combi['combi_quantity']));

            $varcombi->setStockLevel($stockLevel);

            $default = new ProductPriceModel();
            $default->setId($this->identity($varcombi->getId()->getEndpoint().'_default'));
            $default->setProductId($varcombi->getId());
            $default->setCustomerGroupId($this->identity(null));

            $defaultItem = new ProductPriceItemModel();
            $defaultItem->setProductPriceId($default->getId());
            $price = $combi['combi_price_type'] === 'calc' ? floatval($combi['products_price']) + floatval($combi['combi_price']) : floatval($combi['combi_price']);
            $defaultItem->setNetPrice($price);

            $default->addItem($defaultItem);

            $varcombi->setprices(array($default));

            $variationQuery = $this->db->query('SELECT * FROM products_properties_index WHERE products_properties_combis_id='.$combi['products_properties_combis_id']);

            $variations = array();

            foreach ($variationQuery as $variation) {
                if (!isset($variations[$variation['properties_id']])) {
                    $variations[$variation['properties_id']] = $variation;                  
                } 
                
                $variations[$variation['properties_id']]['i18ns'][$variation['language_id']] = array($variation['properties_name'], $variation['values_name']);                
            }

            $variationsArray = array();

            foreach ($variations as $variation) {
                $varModel = new ProductVariationModel();
                $varModel->setId($this->identity($variation['properties_id']));
                $varModel->setProductId($varcombi->getId());
                $varModel->setSort(intval($variation['properties_sort_order']));

                $varValueModel = new ProductVariationValueModel();
                $varValueModel->setId($this->identity($variation['properties_values_id']));
                $varValueModel->setProductVariationId($varModel->getId());
                $varValueModel->setSort(intval($variation['value_sort_order']));

                $variationI18ns = array();
                $variationValueI18ns = array();

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

                $varModel->setValues(array($varValueModel));

                $variationsArray[] = $varModel;
            }

            $varcombi->setVariations($variationsArray);
            
            $return[] = $varcombi;
        }

        return $return;
    }

    public function push($data, $dbObj = null)
    {
        $masterId = $data->getMasterProductId()->getEndpoint();

        if (empty($masterId) && isset(static::$idCache[$data->getMasterProductId()->getHost()])) {
            $masterId = static::$idCache[$data->getMasterProductId()->getHost()];
            $data->getMasterProductId()->setEndpoint($masterId);
        }

        $isVarCombi = !empty($masterId);

        $id = $data->getId()->getEndpoint();
        
        if ($isVarCombi) {
            $this->mapperConfig['mapPush'] = array(
                "ProductVariation|addVariation" => "variations"
            );            
        } else {
            if (!empty($id)) {
                foreach ($this->getCustomerGroups() as $group) {
                    $this->db->query('DELETE FROM personal_offers_by_customers_status_'.$group['customers_status_id'].' WHERE products_id='.$id);
                }

                $this->db->query('DELETE FROM specials WHERE products_id='.$id);
            }            
        }

        return parent::push($data, $dbObj);        
    }

    protected function pushDone($returnModel, $dbObj, $data)
    {
        if ($data->getIsMasterProduct() === true) {
            static::$idCache[$data->getId()->getHost()] = $data->getId()->getEndpoint();
        }

        $checkCodes = $this->db->query('SELECT products_id FROM products_item_codes WHERE products_id='.$data->getId()->getEndpoint());

        $codes = new \stdClass();
        $codes->products_id = $data->getId()->getEndpoint();
        $codes->code_isbn = $data->getIsbn();
        $codes->code_upc = $data->getUpc();
        $codes->code_mpn = $data->getManufacturerNumber();

        foreach ($data->getAttributes() as $attr) {
            foreach ($attr->getI18ns() as $i18n) {
                if ($i18n->getName() === 'Google Zustand') {
                    $codes->google_export_condition = $i18n->getValue();
                } elseif($i18n->getName() === 'Google Verfuegbarkeit ID') {
                    $codes->google_export_availability_id = $i18n->getValue();
                }
            }
        }

        if (count($checkCodes) > 0) {
            $this->db->updateRow($codes, 'products_item_codes', 'products_id', $codes->products_id);
        } else {
            $this->db->insertRow($codes, 'products_item_codes');
        }
        
        //$query = 'INSERT INTO products_quantity_unit SET products_id='.$data->getId()->getEndpoint().' quantity_unit_id='.intval($data->getMeasurementUnitCode());
        //var_dump($query);
    }

    public function delete($data)
    {
        $id = $data->getId()->getEndpoint();

        if (strpos($id, '_') !== false) {
            if (!empty($id) && $id != '') {
                try {
                    $combiId = explode('_', $id);
                    $combiId = $combiId[1];

                    $this->db->query('DELETE FROM products_properties_index WHERE products_properties_combis_id='.$combiId);
                    $this->db->query('DELETE FROM products_properties_combis_values WHERE products_properties_combis_id='.$combiId);
                    $this->db->query('DELETE FROM products_properties_combis WHERE products_properties_combis_id='.$combiId);

                    $this->db->query('DELETE FROM jtl_connector_link WHERE type=64 && endpointId="'.$id.'"');
                }
                catch (\Exception $e) {                
                }
            }
        } else {
            if (!empty($id) && $id != '') {
                try {
                    $this->db->query('DELETE FROM products WHERE products_id='.$id);
                    $this->db->query('DELETE FROM products_to_categories WHERE products_id='.$id);
                    $this->db->query('DELETE FROM products_description WHERE products_id='.$id);
                    $this->db->query('DELETE FROM products_images WHERE products_id='.$id);
                    $this->db->query('DELETE FROM products_attributes WHERE products_id='.$id);
                    $this->db->query('DELETE FROM products_xsell WHERE products_id='.$id.' OR xsell_id='.$id);
                    $this->db->query('DELETE FROM specials WHERE products_id='.$id);
                    $this->db->query('DELETE FROM products_properties_index WHERE products_id='.$id);
                    $this->db->query('DELETE v FROM products_properties_combis_values v LEFT JOIN products_properties_combis c ON c.products_properties_combis_id = v.products_properties_combis_id  WHERE c.products_id='.$id);
                    $this->db->query('DELETE FROM products_properties_combis WHERE products_id='.$id);
                    $this->db->query('DELETE FROM products_quantity_unit WHERE products_id='.$id);
                    $this->db->query('DELETE FROM categories_index WHERE products_id='.$id);
                    $this->db->query('DELETE FROM products_item_codes WHERE products_id='.$id);

                    foreach ($this->getCustomerGroups() as $group) {
                        $this->db->query('DELETE FROM personal_offers_by_customers_status_'.$group['customers_status_id'].' WHERE products_id='.$id);
                    }

                    $this->db->query('DELETE FROM jtl_connector_link WHERE type=64 && endpointId="'.$id.'"');
                }
                catch (\Exception $e) {                
                }
            }
        }

        return $data;
    }

    protected function isMasterProduct($data)
    {
        $query = $this->db->query('SELECT products_properties_combis_id FROM products_properties_combis WHERE products_id='.$data['products_id']);
        return count($query) === 0 ? false : true;
    }

    protected function considerBasePrice($data)
    {
        return $data['products_vpe_status'] == 1 ? true : false;        
    }

    protected function products_vpe($data)
    {
        foreach ($data->getI18ns() as $i18n) {
            $name = $i18n->getUnitName();

            if (!empty($name)) {
                $language_id = $this->locale2id($i18n->getLanguageISO());
                $dbResult = $this->db->query('SELECT code FROM languages WHERE languages_id='.$language_id);

                if ($dbResult[0]['code'] == $this->shopConfig['settings']['DEFAULT_LANGUAGE']) {
                    $sql = $this->db->query('SELECT products_vpe_id FROM products_vpe WHERE language_id='.$language_id.' && products_vpe_name="'.$name.'"');
                    if (count($sql) > 0) {
                        return $sql[0]['products_vpe_id'];
                    }
                }
            }
        }

        return '';
    }

    protected function products_shippingtime($data)
    {
        foreach ($data->getI18ns() as $i18n) {
            $name = $i18n->getDeliveryStatus();

            if (!empty($name)) {
                $language_id = $this->locale2id($i18n->getLanguageISO());
                $dbResult = $this->db->query('SELECT code FROM languages WHERE languages_id='.$language_id);

                if ($dbResult[0]['code'] == $this->shopConfig['settings']['DEFAULT_LANGUAGE']) {
                    $sql = $this->db->query('SELECT shipping_status_id FROM shipping_status WHERE language_id='.$language_id.' && shipping_status_name="'.$name.'"');
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

                            $this->db->deleteInsertRow($status, 'shipping_status', array('shipping_status_id', 'langauge_id'), array($status->shipping_status_id, $status->language_id));
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
            $img = $this->db->query('SELECT products_image FROM products WHERE products_id ='.$id);
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

    protected function unitId($data)
    {
        return $this->replaceZero($data['products_vpe']);
    }

    protected function considerStock($data)
    {
        return true;
    }

    protected function considerVariationStock($data)
    {
        $check = $this->db->query('SELECT products_id FROM products_attributes WHERE products_id='.$data['products_id']);
        
        return count($check) > 0 ? true : false;
    }

    protected function permitNegativeStock($data)
    {
        return $this->shopConfig['settings']['STOCK_ALLOW_CHECKOUT'];
    }

    protected function vat($data)
    {
        $sql = $this->db->query('SELECT r.tax_rate FROM zones_to_geo_zones z LEFT JOIN tax_rates r ON z.geo_zone_id=r.tax_zone_id WHERE z.zone_country_id = '.$this->shopConfig['settings']['STORE_COUNTRY'].' && r.tax_class_id='.$data['products_tax_class_id']);

        if (empty($sql)) {
            $sql = $this->db->query('SELECT tax_rate FROM tax_rates WHERE tax_rates_id='.$this->connectorConfig->tax_rate);
        }

        return floatval($sql[0]['tax_rate']);
    }

    protected function products_tax_class_id($data)
    {
        $sql = $this->db->query('SELECT r.tax_class_id FROM zones_to_geo_zones z LEFT JOIN tax_rates r ON z.geo_zone_id=r.tax_zone_id WHERE z.zone_country_id = '.$this->shopConfig['settings']['STORE_COUNTRY'].' && r.tax_rate='.$data->getVat());
        
        if (empty($sql)) {
            $sql = $this->db->query('SELECT tax_class_id FROM tax_rates WHERE tax_rates_id='.$this->connectorConfig->tax_rate);
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
            LEFT JOIN jtl_connector_link l ON CONVERT(p.products_id, CHAR(16)) = l.endpointId COLLATE utf8_unicode_ci AND l.type = 64 
            WHERE l.hostId IS NULL');

        $combis = $this->db->query('SELECT c.products_properties_combis_id
            FROM products_properties_combis c 
            LEFT JOIN jtl_connector_link l ON CONCAT(c.products_id,"_",c.products_properties_combis_id) = l.endpointId AND l.type = 64 
            WHERE l.hostId IS NULL');

        $count += count($products);
        $count += count($combis);

        return $count;
    }
}
