<?php

namespace jtl\Connector\Gambio\Mapper;

class ImageI18n extends \jtl\Connector\Gambio\Mapper\AbstractMapper
{
    protected $mapperConfig = [
        "getMethod" => "getI18ns",
        "mapPull" => [
            "id" => "img_alt_id",
            "languageISO" => null,
            "imageId" => "image_id",
            "altText" => "gm_alt_text"
        ],
        /*
        "mapPush" => array(
            "language_id" => null,
            "categories_id" => null,
            "categories_name" => "name",
        )
        */
    ];

    public function pull($data = null, $limit = null): array
    {
        $return = [];
        if (isset($data['type'])) {
            switch ($data['type']) {
                case 'product':
                    if (strpos($data['image_id'], 'pID_') !== false) {
                        $this->mapperConfig['query'] = "SELECT d.gm_alt_text,l.code,'" . $data['image_id'] . "' image_id
                            FROM products_description d
                            LEFT JOIN languages l ON l.languages_id=d.language_id
                            WHERE d.products_id='" . $data['foreignKey'] . "'";
                    } else {
                        $this->mapperConfig['query'] = "SELECT d.img_alt_id,d.gm_alt_text,d.image_id,l.code
                          FROM gm_prd_img_alt d
                          LEFT JOIN languages l ON l.languages_id=d.language_id
                          WHERE d.image_id=[[image_id]]";
                    }
                    break;

                case 'category':
                    if (strpos($data['image_id'], 'cID_') !== false) {
                        $this->mapperConfig['query'] = "SELECT d.gm_alt_text,l.code,'" . $data['image_id'] . "' image_id
                            FROM categories_description d
                            LEFT JOIN languages l ON l.languages_id=d.language_id
                            WHERE d.categories_id='" . $data['foreignKey'] . "'";
                    }
                    break;
            }

            $return = parent::pull($data, $limit);
        }
        return $return;
    }

    protected function languageISO($data)
    {
        return $this->fullLocale($data['code']);
    }

    protected function language_id($data)
    {
        return $this->locale2id($data->getLanguageISO());
    }
}
