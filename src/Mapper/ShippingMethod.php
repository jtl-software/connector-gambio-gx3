<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\ShippingMethod as ShippingMethodModel;

class ShippingMethod extends BaseMapper
{
    protected $mapperConfig = [
        "identity" => "getId",
        "getMethod" => "getShippingMethods"
    ];

    public function pull($data = null, $limit = null)
    {
        $moduleStr = $this->configHelper->getDbConfigValue('MODULE_SHIPPING_INSTALLED');

        if (!is_null($moduleStr)) {
            $modules = explode(';', $moduleStr);
            if (count($modules) > 0) {
                $return = [];

                foreach ($modules as $moduleFile) {
                    $modName = str_replace('.php', '', $moduleFile);
                    include_once($this->shopConfig['shop']['path'] . 'lang/german/original_sections/modules/shipping/' . $modName . '.lang.inc.php');

                    if (isset($t_language_text_section_content_array['MODULE_SHIPPING_' . strtoupper($modName) . '_TEXT_TITLE'])) {
                        $modTitle = $t_language_text_section_content_array['MODULE_SHIPPING_' . strtoupper($modName) . '_TEXT_TITLE'];
                    } else {
                        $modTitle = $modName;
                    }

                    $model = new ShippingMethodModel();
                    $model->setName($modTitle);
                    $model->setId(new Identity($modName));

                    $return[] = $model;
                }

                return $return;
            }
        }
    }
}
