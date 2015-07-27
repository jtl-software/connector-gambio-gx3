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
            $attr = new ProductAttrModel();
            $attr->setId($this->identity($field));
            $attr->setProductId($this->identity($data['products_id']));

            $attrI18n = new ProductAttrI18nModel();
            $attrI18n->setProductAttrId($attr->getId());
            $attrI18n->setLanguageISO('ger');
            $attrI18n->setName($name);
            $attrI18n->setValue($data[$field]);

            $attr->setI18ns([$attrI18n]);

            $attrs[] = $attr;
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
                }
            }
        }

        return $data->getAttributes();
    }
}
