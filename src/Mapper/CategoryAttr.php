<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\BaseMapper;
use jtl\Connector\Model\Category as CategoryModel;
use jtl\Connector\Model\CategoryAttr as CategoryAttrModel;
use jtl\Connector\Model\CategoryAttrI18n as CategoryAttrI18nModel;

class CategoryAttr extends BaseMapper
{
    private $additions = [
        'categories_status'          => 'Aktiv',
        'gm_show_qty_info'           => 'Lagerbestand anzeigen',
        'gm_show_attributes'         => 'Attribute anzeigen',
        'gm_show_graduated_prices'   => 'Staffelpreise anzeigen',
        'show_sub_categories'        => 'Unterkategorien anzeigen',
        'show_sub_products'          => 'Artikel aus Unterkategorien anzeigen',
        'show_sub_categories_images' => 'Kategoriebild anzeigen',
        'show_sub_categories_names'  => 'Kategorie Ueberschrift anzeigen',
        'products_sorting'           => 'Produktsortierung ',
        'products_sorting2'          => 'Sortierrichtung',
        'gm_show_qty'                => 'Mengeneingabefeld anzeigen',
        'gm_priority'                => 'Priorität in Sitemap',
        'gm_changefreq'              => 'Änderungsfrequenz in Sitemap',
        'gm_sitemap_entry'           => 'In Sitemap aufnehmen',
        'view_mode_tiledg'           => 'Gekachelte Artikelauflistung',
        'show_category_filter1'      => 'Kategorie-Filter anzeigen',
    ];
    
    public function pull($data = null, $limit = null)
    {
        $attrs = [];
        
        foreach ($this->additions as $field => $name) {
            $attrs[] = $this->createAttr($field, $field, $data[$field], $data);
        }
        
        $hlQuery = $this->db->query('SELECT c.categories_heading_title,c.gm_alt_text,l.code 
            FROM categories_description c
            LEFT JOIN languages l ON l.languages_id=c.language_id
            WHERE c.categories_id=' . $data['categories_id']);
        
        if (count($hlQuery) > 0) {
            $hlAttr = new CategoryAttrModel();
            $hlAttr->setId($this->identity('heading_title'));
            $hlAttr->setCategoryId($this->identity($data['categories_id']));
            $hlAttr->setIsTranslated(true);
            
            $altAttr = new CategoryAttrModel();
            $altAttr->setId($this->identity('alt_text'));
            $altAttr->setCategoryId($this->identity($data['categories_id']));
            $altAttr->setIsTranslated(true);
            
            $hlAttrI18ns = [];
            $altAttrI18ns = [];
            
            foreach ($hlQuery as $headline) {
                $hlAttrI18n = new CategoryAttrI18nModel();
                $hlAttrI18n->setCategoryAttrId($hlAttr->getId());
                $hlAttrI18n->setLanguageISO($this->fullLocale($headline['code']));
                $hlAttrI18n->setName('Überschrift');
                $hlAttrI18n->setValue($headline['categories_heading_title']);
                
                $altAttrI18n = new CategoryAttrI18nModel();
                $altAttrI18n->setCategoryAttrId($altAttr->getId());
                $altAttrI18n->setLanguageISO($this->fullLocale($headline['code']));
                $altAttrI18n->setName('Alternativer Text');
                $altAttrI18n->setValue($headline['gm_alt_text']);
                
                $hlAttrI18ns[] = $hlAttrI18n;
                $altAttrI18ns[] = $altAttrI18n;
            }
            
            $hlAttr->setI18ns($hlAttrI18ns);
            $altAttr->setI18ns($altAttrI18ns);
            
            $attrs[] = $hlAttr;
            $attrs[] = $altAttr;
        }
        
        return $attrs;
    }
    
    /**
     * @param CategoryModel $product
     * @param null $dbObj
     * @return multitype
     */
    public function push($product, $dbObj = null)
    {
        $dbObj->categories_status = 1;
        
        foreach ($product->getAttributes() as $attr) {
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
        
        return $product->getAttributes();
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
