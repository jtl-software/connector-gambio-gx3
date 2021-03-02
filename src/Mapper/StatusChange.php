<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Connector;
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
                $service = Connector::getGxService('OrderWrite');
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
        if ($status->getOrderStatus() == CustomerOrder::STATUS_CANCELLED) {
            return 'canceled';
        } else {
            if ($status->getPaymentStatus() == CustomerOrder::PAYMENT_STATUS_COMPLETED && $status->getOrderStatus() == CustomerOrder::STATUS_SHIPPED) {
                return 'completed';
            } else {
                if ($status->getOrderStatus() == CustomerOrder::STATUS_SHIPPED) {
                    return 'shipped';
                } elseif ($status->getPaymentStatus() == CustomerOrder::PAYMENT_STATUS_COMPLETED) {
                    return 'paid';
                }
            }
        }

        return null;
    }
}
