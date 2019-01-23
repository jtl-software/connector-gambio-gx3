<?php
namespace jtl\Connector\Gambio\Mapper;

class SpecificI18n extends \jtl\Connector\Gambio\Mapper\BaseMapper
{
    protected $mapperConfig = array(
        "table" => "feature_description",
        "getMethod" => "getI18ns",
        "where" => array("feature_id","language_id"),
        "query" => "SELECT feature_description.*, languages.code
            FROM feature_description
            LEFT JOIN languages ON languages.languages_id=feature_description.language_id
            WHERE feature_description.feature_id=[[feature_id]]",
        "mapPull" => array(
            "languageISO" => null,
            "specificId" => "feature_id",
            "name" => "feature_name"
        )
    );

    public function push($parent, $dbObj = null)
    {
        $fId = $parent->getId()->getEndpoint();

        if(!empty($fId)) {
            $data = $parent->getI18ns();

            $currentResults = $this->db->query('SELECT d.language_id FROM feature_description d WHERE d.feature_id="'.$fId.'"');

            $current = array();

            foreach ($currentResults as $fLang) {
                $current[] = $fLang['language_id'];
            }

            $new = array();

            foreach ($data as $obj) {
                $dbObj = new \stdClass();

                $dbObj->language_id = $this->locale2id($obj->getLanguageISO());
                $dbObj->feature_id = $parent->getId()->getEndpoint();
                $dbObj->feature_name = $obj->getName();

                $new[] = $dbObj;
            }

            foreach ($new as $newObj) {
                $existsKey = array_search($newObj->language_id, $current);

                if ($existsKey === false) {
                    $this->db->deleteInsertRow($newObj, $this->mapperConfig['table'], array('feature_id', 'language_id'), array($newObj->feature_id, $newObj->language_id));
                } else {
                    $this->db->updateRow($newObj, $this->mapperConfig['table'], array('feature_id', 'language_id'), array($newObj->feature_id, $newObj->language_id));
                }

                unset($current[$existsKey]);
            }

            foreach ($current as $delId) {
                $this->db->query('DELETE FROM '.$this->mapperConfig['table'].' WHERE feature_id="'.$fId.'" && language_id="'.$delId.'"');
            }
        }
    }

    protected function languageISO($data)
    {
        return $this->fullLocale($data['code']);
    }
}
