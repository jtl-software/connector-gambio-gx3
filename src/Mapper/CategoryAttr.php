<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\AbstractMapper;
use jtl\Connector\Model\Category as CategoryModel;
use jtl\Connector\Model\CategoryAttr as CategoryAttrModel;
use jtl\Connector\Model\CategoryAttrI18n as CategoryAttrI18nModel;
use jtl\Connector\Model\DataModel;

class CategoryAttr extends AbstractMapper
{
    private $additions = [
        'categories_status'          => 'Aktiv',
        'gm_show_qty_info'           => 'Lagerbestand anzeigen',
        'gm_show_attributes'         => 'Attribute anzeigen',
        'gm_show_graduated_prices'   => 'Staffelpreise anzeigen',
        'show_sub_categories'        => 'Unterkategorien anzeigen',
        'show_sub_products'          => 'Artikel aus Unterkategorien anzeigen',
        'show_sub_categories_images' => 'Bilder der Unterkategorien anzeigen',
        'show_sub_categories_names'  => 'Ueberschrift der Unterkategorien anzeigen',
        'products_sorting'           => 'Produktsortierung ',
        'products_sorting2'          => 'Sortierrichtung',
        'gm_show_qty'                => 'Mengeneingabefeld anzeigen',
        'gm_priority'                => 'Priorität in Sitemap',
        'gm_changefreq'              => 'Änderungsfrequenz in Sitemap',
        'gm_sitemap_entry'           => 'In Sitemap aufnehmen',
        'view_mode_tiled'            => 'Gekachelte Artikelauflistung',
        'show_category_filter'       => 'Kategorie-Filter anzeigen',
    ];

    private $relatedColumns = [
        'categories_heading_title' => 'Überschrift',
        'gm_alt_text' => 'Alternativer Text',
    ];
    
    public function pull($data = null, $limit = null): array
    {
        $attrs = [];

        foreach ($this->additions as $field => $name) {
            $attrs[] = $this->createAttr($field, $field, $data[$field], $data);
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
    
    /**
     * @param CategoryModel $product
     * @param null $dbObj
     * @return multitype
     */
    public function push(DataModel $model, \stdClass $dbObj = null)
    {
        $dbObj->categories_status = 1;
        
        foreach ($model->getAttributes() as $attr) {
            foreach ($attr->getI18ns() as $i18n) {
                $attributeName = trim($i18n->getName());
                $field = array_key_exists($attributeName, $this->additions);
                if (!$field) {
                    $result = array_search($attributeName, $this->additions);
                    if (!empty($result)) {
                        $field = true;
                        $attributeName = $result;
                    }
                }
                
                if ($field) {
                    $dbObj->$attributeName = trim($i18n->getValue());
                    break;
                }
            }
        }
        
        return $model->getAttributes();
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
