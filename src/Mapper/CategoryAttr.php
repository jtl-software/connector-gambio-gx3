<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Model\CategoryAttr as CategoryAttrModel;
use jtl\Connector\Model\CategoryAttrI18n as CategoryAttrI18nModel;

class CategoryAttr extends BaseMapper
{
    private $additions = array(
        'categories_status' => 'Aktiv',
        'gm_show_qty_info' => 'Lagerbestand anzeigen',
        'gm_show_attributes' => 'Attribute anzeigen',
        'gm_show_graduated_prices' => 'Staffelpreise anzeigen',
        'show_sub_categories' => 'Unterkategorien anzeigen',
        'show_sub_products' => 'Artikel aus Unterkategorien anzeigen',
        'show_sub_categories_images' => 'Kategoriebild anzeigen',
        'show_sub_categories_names' => 'Kategorie Ueberschrift anzeigen'
    );
    
    private $relatedColumns = [
        'categories_heading_title' => 'Untere Kategoriebeschreibung',
        'gm_alt_text' => 'Alternativer Text',
    ];

    public function pull($data = null, $limit = null) {
        $attrs = array();

        foreach ($this->additions as $field => $name) {
            $attrs[] = $this->createAttr($field, $name, $data[$field], $data);
        }
        
        if (version_compare($this->shopConfig['shop']['version'], '3.11', '>=')) {
            $this->relatedColumns['categories_description_bottom'] = 'categories_description_bottom';
        }

        $sql = sprintf('SELECT l.code,c.%s
                FROM categories_description c
                LEFT JOIN languages l ON l.languages_id=c.language_id
                WHERE c.categories_id= %d', implode(',c.', array_keys($this->relatedColumns)), $data['categories_id']);

        $result = $this->db->query($sql);
        
        foreach ($this->relatedColumns as $column => $name) {
            $attr = new CategoryAttrModel();
            $attr->setId($this->identity($column));
            $attr->setCategoryId($this->identity($data['categories_id']));
            $attr->setIsTranslated(true);
    
            $attrI18ns = [];
            foreach ($result as $row) {
                $attrI18n = new CategoryAttrI18nModel();
                $attrI18n->setCategoryAttrId($attr->getId());
                $attrI18n->setLanguageISO($this->fullLocale($row['code']));
                $attrI18n->setName($name);
                $attrI18n->setValue($row[$column]);
    
                $attrI18ns[] = $attrI18n;
            }
            
            $attrs[] = $attr->setI18ns($attrI18ns);
        }

        return $attrs;
    }

    public function push($data, $dbObj = null) {
        $dbObj->categories_status = 1;

        foreach ($data->getAttributes() as $attr) {
            foreach ($attr->getI18ns() as $i18n) {
                $field = array_search($i18n->getName(), $this->additions);
                if ($field) {
                    $dbObj->$field = $i18n->getValue();
                } elseif ($i18n->getName() == 'Aktiv' && $i18n->getValue() == '0') {
                    $dbObj->categories_status = 0;
                    break;
                }                    
            }            
        }

        return $data->getAttributes();
    }

    private function createAttr($id, $name, $value, $data)
    {
        $attr = new CategoryAttrModel();
        $attr->setId($this->identity($id));
        $attr->setCategoryId($this->identity($data['categories_id']));

        $attrI18n = new CategoryAttrI18nModel();
        $attrI18n->setCategoryAttrId($attr->getId());
        $attrI18n->setLanguageISO('ger');
        $attrI18n->setName($name);
        $attrI18n->setValue($value);

        $attr->setI18ns([$attrI18n]);

        return $attr;
    }
}
