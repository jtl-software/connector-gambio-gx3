<?php
namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\BaseMapper;
use jtl\Connector\Model\CategoryAttr as CategoryAttrModel;
use jtl\Connector\Model\CategoryAttrI18n as CategoryAttrI18nModel;

class CategoryAttr extends BaseMapper
{
    public function pull($data = null, $limit = null) {
        $attrs = array();

        $attr = new CategoryAttrModel();
        $attr->setId($this->identity(1));
        $attr->setCategoryId($this->identity($data['categories_id']));

        $attrI18n = new CategoryAttrI18nModel();
        $attrI18n->setCategoryAttrId($attr->getId());
        $attrI18n->setLanguageISO('ger');
        $attrI18n->setName('Aktiv');
        $attrI18n->setValue($data['categories_status']);

        $attr->setI18ns([$attrI18n]);

        $attrs[] = $attr;

        $hlQuery = $this->db->query('SELECT c.categories_heading_title,l.code 
            FROM categories_description c
            LEFT JOIN languages l ON l.languages_id=c.language_id
            WHERE c.categories_id='.$data['categories_id']);

        if (count($hlQuery) >  0) {
            $hlAttr = new CategoryAttrModel();
            $hlAttr->setId($this->identity(2));
            $hlAttr->setCategoryId($this->identity($data['categories_id']));
            $hlAttr->setIsTranslated(true);

            $hlAttrI18ns = array();

            foreach ($hlQuery as $headline) {
                $hlAttrI18n = new CategoryAttrI18nModel();
                $hlAttrI18n->setCategoryAttrId($hlAttr->getId());
                $hlAttrI18n->setLanguageISO($this->fullLocale($headline['code']));
                $hlAttrI18n->setName('Überschrift');
                $hlAttrI18n->setValue($headline['categories_heading_title']);

                $hlAttrI18ns[] = $hlAttrI18n;
            }

            $hlAttr->setI18ns($hlAttrI18ns);
            $attrs[] = $hlAttr;
        }

        return $attrs;
    }

    public function push($data, $dbObj = null) {
        $dbObj->categories_status = 1;

        foreach ($data->getAttributes() as $attr) {
            foreach ($attr->getI18ns() as $i18n) {
                if ($i18n->getName() == 'Aktiv' && $i18n->getValue() == '0') {
                    $dbObj->categories_status = 0;
                    break;
                }                    
            }            
        }

        return $data->getAttributes();
    }
}
