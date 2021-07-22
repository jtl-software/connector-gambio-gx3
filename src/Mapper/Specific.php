<?php

namespace jtl\Connector\Gambio\Mapper;

class Specific extends \jtl\Connector\Gambio\Mapper\AbstractMapper
{
    protected $mapperConfig = [
        "table" => "feature",
        "query" => "SELECT f.* FROM feature f
            LEFT JOIN jtl_connector_link_specific l ON f.feature_id = l.endpoint_id
            WHERE l.host_id IS NULL",
        "where" => "feature_id",
        "identity" => "getId",
        "mapPull" => [
            "id" => "feature_id",
            "i18ns" => "SpecificI18n|addI18n",
            "values" => "SpecificValue|addValue"
        ],
        "mapPush" => [
            "SpecificI18n|addI18n" => "i18ns",
            // TODO: "SpecificValue|addValue" => "values"
        ]
    ];

    public function push($parent, $dbObj = null)
    {
        $id = $parent->getId()->getEndpoint();

        if (empty($id)) {
            $newId = $this->db->query('INSERT INTO feature SET feature_id=DEFAULT');

            $parent->getId()->setEndpoint($newId);
        }

        return parent::push($parent, $dbObj);
    }

    public function delete($data)
    {
        $id = $data->getId()->getEndpoint();

        if (!empty($id) && $id != '') {
            try {
                /* TODO:
                feature_values mit feature_id loopen {
                    feature_set_values mit feature_value loopen {
                        feature_set_inex mit feature_set_id löschen
                        feature_set_to_products mit feature_set_id löschen
                        feature_set mit feature_set_id löschen
                    }

                    feature_value_description löschen
                    feature_value löschen
                }
                */

                $this->db->query('DELETE FROM feature_description WHERE feature_id='.$data->getId()->getEndpoint());
                $this->db->query('DELETE FROM feature WHERE feature_id='.$data->getId()->getEndpoint());
            } catch (\Exception $e) {
            }
        }

        return $data;
    }
}
