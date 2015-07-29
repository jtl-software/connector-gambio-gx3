<?php
namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\BaseMapper;
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

        return $attrs;
    }

    public function push($data, $dbObj = null) {
        $dbObj->products_status = 1;

        foreach ($data->getAttributes() as $attr) {
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
}
