<?php

namespace jtl\Connector\Gambio\Controller;

use jtl\Connector\Core\Model\DataModel;
use jtl\Connector\Result\Action;

/**
 * Class DeliveryNote
 * @package jtl\Connector\Gambio\Controller
 */
class DeliveryNote extends DefaultController
{
    /**
     * @param DataModel $data
     * @return Action
     */
    public function push(DataModel $data): Action
    {
        $orderId = $data->getCustomerOrderId()->getEndpoint();

        if (!empty($orderId)) {
            $order = $this->db->query(sprintf('SELECT l.languages_id FROM orders AS o LEFT JOIN languages AS l ON o.language = l.directory WHERE orders_id = %s', $orderId));
            if (isset($order[0])) {
                $languageId = $order[0]['languages_id'] ?? 0;
                $carriers = $this->db->query('SELECT * FROM parcel_services');

                foreach ($data->getTrackingLists() as $list) {
                    $carrier = $this->findCarrierCompany($list->getName(), $carriers);
                    if ($carrier !== null) {
                        $trackingUrlTemplate = $this->getTrackingUrlTemplate((int)$languageId, (int)$carrier['parcel_service_id']);
                        foreach ($list->getCodes() as $code) {
                            $trackingUrl = str_replace('{TRACKING_NUMBER}', $code, $trackingUrlTemplate);
                            $this->db->query(
                                sprintf(
                                'INSERT INTO orders_parcel_tracking_codes (order_id, tracking_code, parcel_service_id, parcel_service_name, language_id, url, comment)
                                            SELECT "%s", "%s", %s, "%s", "%s", "%s", "" 
                                                FROM dual 
                                                WHERE NOT EXISTS (SELECT * FROM orders_parcel_tracking_codes WHERE tracking_code = "%s" AND parcel_service_id = %s)',
                                    $orderId,
                                    $code,
                                    $carrier['parcel_service_id'],
                                    $carrier['name'],
                                    $languageId,
                                    $trackingUrl,
                                    $code,
                                    $carrier['parcel_service_id']
                                )
                            );
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

    /**
     * @param string $deliveryCompanyName
     * @param array $carriers
     * @return array|null
     */
    protected function findCarrierCompany(string $deliveryCompanyName, array $carriers): ?array
    {
        $searchResultLength = 0;
        $searchResult = null;
        $sameSearchResultQuantity = 0;

        foreach ($carriers as $carrier) {
            $carrierNameLength = strlen($carrier['name']);

            $companyStartsWithProviderName = strpos($deliveryCompanyName, $carrier['name']) !== false;
            $newResultIsMoreSimilarThanPrevious = $carrierNameLength > $searchResultLength;
            $newResultHasSameLengthAsPrevious = $carrierNameLength === $searchResultLength;

            if ($companyStartsWithProviderName) {
                if ($newResultIsMoreSimilarThanPrevious) {
                    $searchResult = $carrier;
                    $searchResultLength = $carrierNameLength;
                    $sameSearchResultQuantity = 0;
                } elseif ($newResultHasSameLengthAsPrevious) {
                    $sameSearchResultQuantity++;
                    $searchResult = null;
                }
            }
        }

        return $searchResult;
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
        return $parcelServiceDescription[0]['url'] ?? '';
    }
}
