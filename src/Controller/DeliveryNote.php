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

            $order = $this->db->query(sprintf('SELECT l.languages_id FROM orders AS o LEFT JOIN languages AS l ON o.language = l.directory WHERE orders_id = %s', $orderId));
            if(isset($order[0])) {
                $languageId = $order[0]['languages_id'] ?? 0;
                $carriers = $this->db->query('SELECT * FROM parcel_services');

                foreach ($data->getTrackingLists() as $list) {
                    foreach ($carriers as $carrier) {
                        if ($list->getName() == $carrier['name']) {
                            $trackingUrlTemplate = $this->getTrackingUrlTemplate((int)$languageId, (int)$carrier['parcel_service_id']);
                            foreach ($list->getCodes() as $code) {
                                $trackingUrl = str_replace('{TRACKING_NUMBER}', $code, $trackingUrlTemplate);
                                $this->db->query(
                                    sprintf('
                                        INSERT INTO orders_parcel_tracking_codes SET
                                        order_id="%s", tracking_code="%s", parcel_service_id=%s, parcel_service_name="%s", language_id="%s", url="%s", comment=""',
                                        $orderId, $code, $carrier['parcel_service_id'], $carrier['name'], $languageId, $trackingUrl
                                    )
                                );
                            }
                        }
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

    /**
     * @param int $languageId
     * @param int $parcelServiceId
     * @return string|null
     */
    protected function getTrackingUrlTemplate(int $languageId, int $parcelServiceId): ?string
    {
        $parcelServiceDescription = $this->db->query(
            sprintf('SELECT psd.url FROM parcel_services_description AS psd WHERE psd.language_id = %s AND psd.parcel_service_id = %s', $languageId, $parcelServiceId)
        );
        return $parcelServiceDescription[0]['url'] ?? '{TRACKING_NUMBER}';
    }
}
