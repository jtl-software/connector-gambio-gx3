<?php

namespace jtl\Connector\Gambio\Mapper;

use \jtl\Connector\Model\CustomerGroupAttr as CustomerGroupAttrModel;

class CustomerGroupAttr extends BaseMapper
{
    public function pull($data = null, $limit = null)
    {
        $attrs = [];

        if (isset($data['customers_status_min_order'])) {
            $min = new CustomerGroupAttrModel();
            $min->setId($this->identity('min'));
            $min->setCustomerGroupId($this->identity($data['customers_status_id']));
            $min->setKey('Mindestbestellwert');
            if (isset($data['customers_status_min_order'])) {
                $min->setValue($data['customers_status_min_order']);
            }

            $attrs[] = $min;
        }

        if (isset($data['customers_status_max_order'])) {
            $max = new CustomerGroupAttrModel();
            $max->setId($this->identity('max'));
            $max->setCustomerGroupId($this->identity($data['customers_status_id']));
            $max->setKey('Hoechstbestellwert');
            if (isset($data['customers_status_max_order'])) {
                $max->setValue($data['customers_status_max_order']);
            }

            $attrs[] = $max;
        }

        return $attrs;
    }
}
