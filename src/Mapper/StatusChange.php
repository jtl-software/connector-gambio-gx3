<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Connector;
use jtl\Connector\Gambio\Gambio\Application;
use jtl\Connector\Model\StatusChange as StatusChangeModel;
use jtl\Connector\Model\CustomerOrder;

class StatusChange extends BaseMapper
{
    /**
     * @param StatusChangeModel $status
     * @return StatusChangeModel
     */
    public function push(StatusChangeModel $status)
    {
        $customerOrderId = (int) $status->getCustomerOrderId()->getEndpoint();

        if ($customerOrderId > 0) {
            $mapping = (array) $this->connectorConfig->mapping;
            
            $newStatus = $mapping[$this->getStatus($status)] ?? null;

            if (!is_null($newStatus)) {
                /** @var \OrderWriteService $service */
                $service = Connector::getGxService(Application::SERVICE_ORDER_WRITE);
                $service->updateOrderStatus(new \IdType($customerOrderId), new \IntType($newStatus), new \StringType(''), new \BoolType(false));
                $service->addOrderStatusHistoryEntry(new \IdType($customerOrderId), new \StringType(''), new \IdType(0));
            }
        }

        return $status;
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
