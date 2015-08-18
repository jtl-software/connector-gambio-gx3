<?php
namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\BaseMapper;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\ProductAttr as ProductAttrModel;
use jtl\Connector\Model\ProductAttrI18n as ProductAttrI18nModel;

class ProductAttr extends BaseMapper
{
    private $additions = array(
        'products_status' => 'Aktiv',
        'gm_price_status' => 'Preis-Status',
        'gm_show_qty_info' => 'Lagerbestand anzeigen',
        'gm_show_weight' => 'Gewicht anzeigen',
        'products_fsk18' => 'FSK 18'
    );

    public function pull($data = null, $limit = null) {
        $attrs = array();

        foreach ($this->additions as $field => $name) {
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

        $attrs = array_merge($attrs, $this->pullCustoms($data));

        return $attrs;
    }

    public function push($data, $dbObj = null) {
        $dbObj->products_status = 1;

        foreach ($data->getAttributes() as $attr) {
            if ($attr->getIsCustomProperty() === false) {
                foreach ($attr->getI18ns() as $i18n) {
                    $field = array_search($i18n->getName(), $this->additions);
                    if ($field) {
                        $dbObj->$field = $i18n->getValue();
                    } elseif ($i18n->getName() === 'Google Kategorie') {
                        $obj = new \stdClass();
                        $obj->products_id = $data->getId()->getEndpoint();
                        $obj->google_category = $i18n->getValue();
                        $this->db->deleteInsertRow($obj, 'products_google_categories', 'products_id', $obj->products_id);
                    }
                }
            } else {
                foreach ($attr->getI18ns() as $i18n) {
                    $language_id = $this->locale2id($i18n->getLanguageISO());
                    $dbResult = $this->db->query('SELECT code FROM languages WHERE languages_id=' . $language_id);

                    if ($dbResult[0]['code'] == $this->shopConfig['settings']['DEFAULT_LANGUAGE']) {
                        $sql = $this->db->query('SELECT additional_field_id FROM additional_field_descriptions WHERE language_id='.$language_id.' AND name="'.$i18n->getName().'"');
                        if (count($sql) > 0) {
                            $fieldId = $sql[0]['additional_field_id'];

                            $this->db->query('
                              DELETE v, d
                              FROM additional_field_values v
                              LEFT JOIN additional_field_value_descriptions d ON d.additional_field_value_id = v.additional_field_value_id
                              WHERE v.additional_field_id = '.$fieldId.' AND item_id='.$data->getId()->getEndpoint()
                            );
                        } else {
                            $field = new \stdClass();
                            $field->field_key = 'product-'.uniqid();
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
                        $valDesc->value = $i18n->getName();

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
          WHERE v.item_id="'.$data['products_id'].'"');

        foreach ($fields as $attrData) {
            $attr = new ProductAttrModel();
            $attr->setProductId(new Identity($data['products_id']));
            $attr->setId(new Identity($attrData['additional_field_id']));
            $attr->setIsTranslated(boolval($attrData['multilingual']));
            $attr->setIsCustomProperty(true);

            $multiLang = $attr->getIsTranslated() ? ' AND d.language_id = v.language_id' : '';

            $values = $this->db->query('
                SELECT v.value, d.name, d.language_id as lang
                FROM additional_field_value_descriptions v
                LEFT JOIN additional_field_values f ON f.additional_field_value_id = v.additional_field_value_id
                LEFT JOIN additional_field_descriptions d ON d.additional_field_id = f.additional_field_id'.$multiLang.'
                WHERE v.additional_field_value_id = '.$attrData['additional_field_value_id']
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
}
