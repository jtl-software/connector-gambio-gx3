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
    
    protected $relatedAttributes = [
        'categories_description_bottom'
    ];

    public function push($parent, $dbObj = null)
    {
        $cId = $parent->getId()->getEndpoint();

        if(!empty($cId)) {
            $data = $parent->getI18ns();

            $currentResults = $this->db->query('SELECT d.language_id FROM categories_description d WHERE d.categories_id="'.$cId.'"');

            $current = array();

            foreach ($currentResults as $cLang) {
                $current[] = $cLang['language_id'];
            }

            $new = [];
            
            $attributes = [];
            foreach($parent->getAttributes() as $attribute) {
                foreach($attribute->getI18ns() as $i18n) {
                    if(in_array($i18n->getName(), $this->relatedAttributes)) {
                        $attributes[$i18n->getLanguageISO()][$i18n->getName()] = $i18n;
                    }
                }
            }

            foreach ($data as $obj) {
                if (!$this->type) {
                    $this->type = $obj->getModelType();
                }

                $dbObj = new \stdClass();
                $dbObj->categories_description = '';
                $dbObj->categories_meta_title = '';
                $dbObj->categories_meta_description = '';
                $dbObj->categories_meta_keywords = '';
                if (version_compare($this->shopConfig['shop']['version'], '3.11', '>=')) {
                    $dbObj->categories_description_bottom = '';
                }
                
                
                foreach($attributes[$obj->getLanguageISO()] as $key => $attribute) {
                    if(property_exists($dbObj, $key)) {
                        $dbObj->$key = $attribute->getValue();
                    }
                }
                
                foreach ($this->mapperConfig['mapPush'] as $endpoint => $host) {
                    if (is_null($host) && method_exists(get_class($this), $endpoint)) {
                        $dbObj->$endpoint = $this->$endpoint($obj, null, $parent);
                    } else {
                        $value = null;

                        $getMethod = 'get' . ucfirst($host);

                        if (isset($obj) && method_exists($obj, $getMethod)) {
                            $value = $obj->$getMethod();
                        }

                        if (isset($value)) {
                            if ($this->type->getProperty($host)->isIdentity()) {
                                $value = $value->getEndpoint();
                            } else {
                                $type = $this->type->getProperty($host)->getType();
                                if ($type == "DateTime") {
                                    $value = $value->format('Y-m-d H:i:s');
                                } elseif ($type == "boolean") {
                                    settype($value, "integer");
                                }
                            }

                            $dbObj->$endpoint = $value;
                        }
                    }
                }

                $new[] = $dbObj;
            }

            foreach ($new as $newObj) {
                $existsKey = array_search($newObj->language_id, $current);

                if ($existsKey === false) {
                    $res = $this->db->deleteInsertRow($newObj, $this->mapperConfig['table'], array('categories_id', 'language_id'), array($newObj->categories_id, $newObj->language_id));
                } else {
                    $this->db->updateRow($newObj, $this->mapperConfig['table'], array('categories_id', 'language_id'), array($newObj->categories_id, $newObj->language_id));
                }

                unset($current[$existsKey]);
            }

            foreach ($current as $delId) {
                $this->db->query('DELETE FROM '.$this->mapperConfig['table'].' WHERE categories_id="'.$cId.'" && language_id="'.$delId.'"');
            }
        }
    }

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
