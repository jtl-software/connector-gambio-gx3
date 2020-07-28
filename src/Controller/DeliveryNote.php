<?php

namespace jtl\Connector\Gambio\Controller;

use jtl\Connector\Core\Controller\Controller;
use jtl\Connector\Core\Model\QueryFilter;
use jtl\Connector\Core\Model\DataModel;
use jtl\Connector\Result\Action;
use jtl\Connector\Core\Database\Mysql;

class DeliveryNote extends Controller
{
    protected $db;

    public function __construct()
    {
        $this->db = Mysql::getInstance();
    }

    public function statistic(QueryFilter $filter)
    {
    }

    public function pull(QueryFilter $queryfilter)
    {
    }

    public function push(DataModel $data)
    {
        $orderId = $data->getCustomerOrderId()->getEndpoint();

        if (!empty($orderId)) {
            $carriers = $this->db->query('SELECT * FROM parcel_services');

            foreach ($data->getTrackingLists() as $list) {
                foreach ($carriers as $carrier) {
                    if ($list->getName() == $carrier['name']) {
                        $this->db->query('INSERT INTO orders_parcel_tracking_codes SET
                          order_id="'.$orderId.'",
                          tracking_code="'.implode(', ', $list->getCodes()).'",
                          parcel_service_id='.$carrier['parcel_service_id'].',
                          parcel_service_name="'.$carrier['name'].'",
                          comment=""
                        ');

                        break;
                    }
                }
            }
        }

        $action = new Action();
        $action->setHandled(true);
        $action->setResult($data);

        return $action;
    }

    public function delete(DataModel $model)
    {
    }
}
