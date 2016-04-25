<?php
namespace jtl\Connector\Gambio\Mapper;

class CategoryI18n extends \jtl\Connector\Gambio\Mapper\BaseMapper
{
    protected $mapperConfig = array(
        "table" => "categories_description",
        "getMethod" => "getI18ns",
        "where" => array("categories_id","language_id"),
        "query" => "SELECT categories_description.*,languages.code
            FROM categories_description
            LEFT JOIN languages ON languages.languages_id=categories_description.language_id
            WHERE categories_description.categories_id=[[categories_id]]",
        "mapPull" => array(
            "languageISO" => null,
            "categoryId" => "categories_id",
            "name" => "categories_name",
            "description" => "categories_description",
            "metaDescription" => "categories_meta_description",
            "metaKeywords" => "categories_meta_keywords",
            "titleTag" => "categories_meta_title",
            "urlPath" => "gm_url_keywords"
        ),
        "mapPush" => array(
            "language_id" => null,
            "categories_id" => null,
            "categories_name" => "name",
            "categories_description" => "description",
            "categories_meta_description" => "metaDescription",
            "categories_meta_keywords" => "metaKeywords",
            "categories_meta_title" => "titleTag",
            "gm_url_keywords" => null,
            "categories_heading_title" => null,
            "gm_alt_text" => null
        )
    );

    protected function languageISO($data)
    {
        return $this->fullLocale($data['code']);
    }

    protected function language_id($data)
    {
        return $this->locale2id($data->getLanguageISO());
    }

    protected function categories_id($data, $return, $parent)
    {
        $return->setCategoryId($this->identity($parent->getId()->getEndpoint()));

        return $parent->getId()->getEndpoint();
    }

    protected function categories_heading_title($data, $return, $parent)
    {
        foreach ($parent->getAttributes() as $attr) {
            foreach ($attr->getI18ns() as $i18n) {
                if ($i18n->getName() == "Überschrift") {
                    if ($i18n->getLanguageISO() == $data->getLanguageISO()) {
                        return $i18n->getValue();
                    }                               
                }
            }
        }

        return '';
    }

    protected function gm_alt_text($data, $return, $parent)
    {
        foreach ($parent->getAttributes() as $attr) {
            foreach ($attr->getI18ns() as $i18n) {
                if ($i18n->getName() == "Alternativer Text") {
                    if ($i18n->getLanguageISO() == $data->getLanguageISO()) {
                        return $i18n->getValue();
                    }
                }
            }
        }

        return '';
    }

    protected function gm_url_keywords($data)
    {
        return $this->cleanName($data->getUrlPath());
    }
}
