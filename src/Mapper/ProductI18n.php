<?php

namespace jtl\Connector\Gambio\Mapper;

class ProductI18n extends AbstractMapper
{
    protected $mapperConfig = [
        "table" => "products_description",
        "query" => "SELECT products_description.*,languages.code
            FROM products_description
            LEFT JOIN languages ON languages.languages_id=products_description.language_id
            WHERE products_id=[[products_id]]",
        "getMethod" => "getI18ns",
        "where" => ["products_id","language_id"],
        "mapPull" => [
            "languageISO" => null,
            "productId" => "products_id",
            "name" => "products_name",
            "description" => "products_description",
            "metaDescription" => "products_meta_description",
            "metaKeywords" => "products_meta_keywords",
            "shortDescription" => "products_short_description",
            "titleTag" => "products_meta_title",
            "unitName" => null,
            "measurementUnitName" => null,
            "deliveryStatus" => null,
            "urlPath" => "gm_url_keywords"
        ],
        "mapPush" => [
            "language_id" => null,
            "products_name" => "name",
            "products_description" => "description",
            "products_meta_description" => "metaDescription",
            "products_meta_keywords" => "metaKeywords",
            "products_short_description" => "shortDescription",
            "products_meta_title" => "titleTag",
            "gm_url_keywords" => null,
            "checkout_information" => null
        ]
    ];
    
    protected function deliveryStatus($data)
    {
        $query = $this->db->query('SELECT s.shipping_status_name
            FROM shipping_status s
            LEFT JOIN products p ON p.products_shippingtime = s.shipping_status_id
            WHERE p.products_id ='.$data['products_id'].' && s.language_id ='.$data['language_id']);

        if (count($query) > 0) {
            return $query[0]['shipping_status_name'];
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

    protected function unitName($data)
    {
        $sql = $this->db->query('SELECT p.products_id, v.products_vpe_name
            FROM products p
            LEFT JOIN products_vpe v ON v.products_vpe_id = p.products_vpe
            WHERE products_id='.$data['products_id'].' && v.language_id='.$data['language_id']);

        if (count($sql) > 0) {
            return $sql[0]['products_vpe_name'];
        }
    }

    protected function measurementUnitName($data)
    {
        $sql = $this->db->query('SELECT q.unit_name
          FROM products_quantity_unit p
          LEFT JOIN quantity_unit_description q
          ON q.quantity_unit_id = p.quantity_unit_id
          WHERE p.products_id='.$data['products_id'].' && q.language_id='.$data['language_id']);

        if (count($sql) > 0) {
            return $sql[0]['unit_name'];
        }
    }

    protected function gm_url_keywords($data)
    {
        return $this->cleanName($data->getUrlPath());
    }

    protected function checkout_information($data)
    {
        return '';
    }

    public function push($parent, $dbObj = null)
    {
        $pId = $parent->getId()->getEndpoint();

        if (!empty($pId)) {
            $data = $parent->getI18ns();

            $currentResults = $this->db->query('SELECT d.language_id FROM products_description d WHERE d.products_id="'.$pId.'"');

            $current = [];

            foreach ($currentResults as $cLang) {
                $current[] = $cLang['language_id'];
            }

            $new = [];

            foreach ($data as $obj) {
                if (!$this->type) {
                    $this->type = $obj->getModelType();
                }

                $dbObj = new \stdClass();
                $dbObj->products_id = $pId;

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
                    $this->db->deleteInsertRow($newObj, $this->mapperConfig['table'], ['products_id', 'language_id'], [$newObj->products_id, $newObj->language_id]);
                } else {
                    $this->db->updateRow($newObj, $this->mapperConfig['table'], ['products_id', 'language_id'], [$newObj->products_id, $newObj->language_id]);
                }

                unset($current[$existsKey]);
            }

            foreach ($current as $delId) {
                $this->db->query('DELETE FROM '.$this->mapperConfig['table'].' WHERE products_id="'.$pId.'" && language_id="'.$delId.'"');
            }
        }
    }
}
