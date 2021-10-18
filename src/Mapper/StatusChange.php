<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Connector;
use jtl\Connector\Gambio\Gambio\Application;
use jtl\Connector\Model\DataModel;
use jtl\Connector\Model\StatusChange as StatusChangeModel;
use jtl\Connector\Model\CustomerOrder;

class StatusChange extends AbstractMapper
{
    /**
     * @param DataModel $model
     * @param \stdClass|null $dbObj
     * @return DataModel
     */
    public function push(DataModel $model, \stdClass $dbObj = null)
    {
        /** @var StatusChangeModel $model */
        $customerOrderId = (int) $model->getCustomerOrderId()->getEndpoint();

        if ($customerOrderId > 0) {
            $mapping = (array) $this->connectorConfig->mapping;
            
            $newStatus = $mapping[$this->getStatus($model)] ?? null;

            if (!is_null($newStatus)) {
                /** @var \OrderWriteService $service */
                $service = Connector::getGxService(Application::SERVICE_ORDER_WRITE);
                $service->updateOrderStatus(new \IdType($customerOrderId), new \IntType($newStatus), new \StringType(''), new \BoolType(false));
                $service->addOrderStatusHistoryEntry(new \IdType($customerOrderId), new \StringType(''), new \IdType(0));
            }
        }

        return $model;
    }

    /**
     * @param StatusChangeModel $status
     * @return string|null
     */
    private function getStatus(StatusChangeModel $status): ?string
    {
        $statusName = null;
        if ($status->getOrderStatus() == CustomerOrder::STATUS_CANCELLED) {
            $statusName = 'canceled';
        } else {
            if ($status->getPaymentStatus() == CustomerOrder::PAYMENT_STATUS_COMPLETED && $status->getOrderStatus() == CustomerOrder::STATUS_SHIPPED) {
                $statusName = 'completed';
            } else {
                if ($status->getOrderStatus() == CustomerOrder::STATUS_SHIPPED) {
                    $statusName = 'shipped';
                } elseif ($status->getPaymentStatus() == CustomerOrder::PAYMENT_STATUS_COMPLETED) {
                    $statusName = 'paid';
                }
            }
        }

        return $statusName;
    }
}
