<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\BaseMapper;

class MeasurementUnitI18n extends BaseMapper
{
    protected $mapperConfig = [
        "query" => "SELECT quantity_unit_description.*,languages.code FROM quantity_unit_description LEFT JOIN languages ON languages.languages_id=quantity_unit_description.language_id WHERE quantity_unit_id=[[quantity_unit_id]]",
        "table" => "quantity_unit_description",
        "getMethod" => "getI18ns",
        "mapPull" => [
            "measurementUnitId" => "quantity_unit_id",
            "languageISO" => null,
            "name" => "unit_name"
        ]
    ];

    public function push($data, $dbObj = null)
    {
        $id = null;

        $skip = true;

        foreach ($data->getI18ns() as $i18n) {
            $name = $i18n->getName();
            $language_id = $this->locale2id($i18n->getLanguageISO());
            
            $dbResult = $this->db->query('SELECT code FROM languages WHERE languages_id='.$language_id);

            if ($dbResult[0]['code'] == $this->shopConfig['settings']['DEFAULT_LANGUAGE']) {
                $sql = $this->db->query('SELECT quantity_unit_id FROM quantity_unit_description WHERE language_id='.$language_id.' && unit_name="'.$name.'"');
                if (count($sql) > 0) {
                    $id = $sql[0]['quantity_unit_id'];
                }
            }

            if (!empty($name)) {
                $skip = false;
            }
        }

        if ($skip === false) {
            if (is_null($id)) {
                $id = $this->db->query("INSERT INTO quantity_unit (quantity_unit_id) VALUES (NULL)");
            } else {
                $this->db->query('DELETE FROM quantity_unit_description WHERE quantity_unit_id=' . $id);
            }

            $data->getId()->setEndpoint($id);

            foreach ($data->getI18ns() as $i18n) {
                $i18n->getMeasurementUnitId()->setEndpoint($id);

                $unit = new \stdClass();
                $unit->language_id = $this->locale2id($i18n->getLanguageISO());
                $unit->quantity_unit_id = $id;
                $unit->unit_name = $i18n->getName();

                $this->db->insertRow($unit, 'quantity_unit_description');
            }

            return $data->getI18ns();
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
}
