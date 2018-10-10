<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\ProductAttr as ProductAttrModel;
use jtl\Connector\Model\ProductAttrI18n as ProductAttrI18nModel;

class ProductAttr extends BaseMapper
{
    protected $ignoreAttributes = [
        'Wesentliche Produktmerkmale',
        'Google Kategorie',
        'Google Zustand',
        'Google Verfuegbarkeit ID'
    ];

    public function pull($data = null, $limit = null)
    {
        $attrs = array();

        foreach (Product::getSpecialAttributes() as $field => $name) {
            $attrs[] = $this->createAttr($field, $name, $data[$field], $data);
        }

        if (!empty($data['google_category'])) {
            $attrs[] = $this->createAttr('google_category', 'Google Kategorie', $data['google_category'], $data);
        }

        if (!empty($data['google_export_condition'])) {
            $attrs[] = $this->createAttr('google_export_condition', 'Google Zustand', $data['google_export_condition'], $data);
        }

        if (!empty($data['google_export_availability_id'])) {
            $attrs[] = $this->createAttr('google_export_availability_id', 'Google Verfuegbarkeit ID', $data['google_export_availability_id'], $data);
        }

        $attrs = array_merge($attrs, $this->pullCustoms($data), $this->pullMultiAttrs($data));

        return $attrs;
    }

    public function push($data, $dbObj = null)
    {
        $ignoreAttributes = array_merge($this->ignoreAttributes, Product::getSpecialAttributes());
        foreach ($data->getAttributes() as $attr) {
            foreach ($attr->getI18ns() as $i18n) {
                $pId = $data->getId()->getEndpoint();
                $ignoreAttribute = in_array($i18n->getName(), $ignoreAttributes);
                if ($ignoreAttribute) {
                    break;
                } else {
                    $language_id = $this->locale2id($i18n->getLanguageISO());
                    $dbResult = $this->db->query('SELECT code FROM languages WHERE languages_id=' . $language_id);

                    if ($dbResult[0]['code'] == $this->shopConfig['settings']['DEFAULT_LANGUAGE']) {
                        $sql = $this->db->query('SELECT additional_field_id FROM additional_field_descriptions WHERE language_id=' . $language_id . ' AND name="' . $i18n->getName() . '"');
                        if (count($sql) > 0) {
                            $fieldId = $sql[0]['additional_field_id'];

                            if (!empty($pId)) {
                                $this->db->query('
                                      DELETE v, d
                                      FROM additional_field_values v
                                      LEFT JOIN additional_field_value_descriptions d ON d.additional_field_value_id = v.additional_field_value_id
                                      WHERE v.additional_field_id = ' . $fieldId . ' AND item_id=' . $pId
                                );
                            }
                        } else {
                            $field = new \stdClass();
                            $field->field_key = 'product-' . uniqid();
                            $field->item_type = 'product';
                            $field->multilingual = strval($attr->getIsTranslated());

                            $insResult = $this->db->insertRow($field, 'additional_fields');
                            $fieldId = $insResult->getKey();

                            foreach ($attr->getI18ns() as $i18n) {
                                $fieldDesc = new \stdClass();
                                $fieldDesc->additional_field_id = $fieldId;
                                $fieldDesc->language_id = $this->locale2id($i18n->getLanguageISO());
                                $fieldDesc->name = $i18n->getName();

                                $this->db->deleteInsertRow($fieldDesc, 'additional_field_descriptions', array('additional_field_id', 'language_id'), array($fieldDesc->additional_field_id, $fieldDesc->language_id));
                            }
                        }
                    }
                }
            }

            $value = new \stdClass();
            $value->additional_field_id = $fieldId;
            $value->item_id = $data->getId()->getEndpoint();

            $valIns = $this->db->insertRow($value, 'additional_field_values');
            $valId = $valIns->getKey();

            if ($attr->getIsTranslated() === true) {
                foreach ($attr->getI18ns() as $i18n) {
                    $valDesc = new \stdClass();
                    $valDesc->additional_field_value_id = $valId;
                    $valDesc->language_id = $this->locale2id($i18n->getLanguageISO());
                    $valDesc->value = $i18n->getValue();

                    $this->db->insertRow($valDesc, 'additional_field_value_descriptions');
                }
            } else {
                $valDesc = new \stdClass();
                $valDesc->additional_field_value_id = $valId;
                $valDesc->language_id = 0;
                $valDesc->value = $attr->getI18ns()[0]->getValue();

                $this->db->insertRow($valDesc, 'additional_field_value_descriptions');
            }
        }

        return $data->getAttributes();
    }

    private function createAttr($id, $name, $value, $data)
    {
        $attr = new ProductAttrModel();
        $attr->setId($this->identity($id));
        $attr->setProductId($this->identity($data['products_id']));

        $attrI18n = new ProductAttrI18nModel();
        $attrI18n->setProductAttrId($attr->getId());
        $attrI18n->setLanguageISO('ger');
        $attrI18n->setName($name);
        $attrI18n->setValue($value);

        $attr->setI18ns([$attrI18n]);

        return $attr;
    }

    private function pullCustoms($data)
    {
        $customs = array();

        $fields = $this->db->query('
          SELECT f.*, v.additional_field_value_id
          FROM additional_field_values v
          LEFT JOIN additional_fields f ON f.additional_field_id = v.additional_field_id
          WHERE v.item_id="' . $data['products_id'] . '"');

        foreach ($fields as $attrData) {
            $attr = new ProductAttrModel();
            $attr->setProductId(new Identity($data['products_id']));
            $attr->setId(new Identity($attrData['additional_field_id']));
            $attr->setIsTranslated((bool)$attrData['multilingual']);
            $attr->setIsCustomProperty(true);

            $multiLang = $attr->getIsTranslated() ? ' AND d.language_id = v.language_id' : '';

            $values = $this->db->query('
                SELECT v.value, d.name, d.language_id as lang
                FROM additional_field_value_descriptions v
                LEFT JOIN additional_field_values f ON f.additional_field_value_id = v.additional_field_value_id
                LEFT JOIN additional_field_descriptions d ON d.additional_field_id = f.additional_field_id' . $multiLang . '
                WHERE v.additional_field_value_id = ' . $attrData['additional_field_value_id']
            );

            foreach ($values as $valueData) {
                $i18n = new ProductAttrI18nModel();
                $i18n->setProductAttrId($attr->getId());
                $i18n->setValue($valueData['value']);
                $i18n->setName($valueData['name']);
                $i18n->setLanguageISO($this->id2locale($valueData['lang']));

                $attr->addI18n($i18n);
            }

            $customs[] = $attr;
        }

        return $customs;
    }

    private function pullMultiAttrs($data)
    {
        $multiData = $this->db->query('
          SELECT language_id, checkout_information
          FROM products_description
          WHERE products_id="' . $data['products_id'] . '" && checkout_information != ""');

        if (count($multiData) > 0) {
            $checkoutInfo = new ProductAttrModel();
            $checkoutInfo->setId(new Identity('checkout_information'));
            $checkoutInfo->setProductId(new Identity($data['products_id']));
            $checkoutInfo->setIsTranslated(true);

            foreach ($multiData as $i18nData) {
                $i18n = new ProductAttrI18nModel();
                $i18n->setProductAttrId($checkoutInfo->getId());
                $i18n->setValue($i18nData['checkout_information']);
                $i18n->setName('Wesentliche Produktmerkmale');
                $i18n->setLanguageISO($this->id2locale($i18nData['language_id']));

                $checkoutInfo->addI18n($i18n);
            }

            return array($checkoutInfo);
        }

        return array();
    }
}
