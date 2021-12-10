<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Core\Database\IDatabase;
use jtl\Connector\Model\CustomerOrder as CustomerOrderModel;
use jtl\Connector\Payment\PaymentTypes;
use jtl\Connector\Model\CustomerOrderItem;

class CustomerOrder extends AbstractMapper
{
    protected $mapperConfig = [
        "table" => "orders",
        "query" => "SELECT o.* FROM orders o
            LEFT JOIN jtl_connector_link_customer_order l ON o.orders_id = l.endpoint_id
            WHERE l.host_id IS NULL",
        "statisticsQuery" => "SELECT COUNT(o.orders_id) as total FROM orders o
            LEFT JOIN jtl_connector_link_customer_order l ON o.orders_id = l.endpoint_id
            WHERE l.host_id IS NULL",
        "where" => "orders_id",
        "identity" => "getId",
        "mapPull" => [
            "id" => "orders_id",
            "orderNumber" => "orders_id",
            "customerId" => "customers_id",
            "creationDate" => "date_purchased",
            "customerNote" => "comments",
            "paymentModuleCode" => null,
            "currencyIso" => "currency",
            "billingAddress" => "CustomerOrderBillingAddress|setBillingAddress",
            "shippingAddress" => "CustomerOrderShippingAddress|setShippingAddress",
            "items" => "CustomerOrderItem|addItem",
            "status" => null,
            "paymentStatus" => null,
            "languageISO" => null,
        ],
        "mapPush" => [
            "orders_id" => "id",
            "customers_id" => "customerId",
            "date_purchased" => "creationDate",
            "comments" => "note",
            "orders_status" => null,
            //"payment_method" => null,
            //"payment_class" => null,
            "currency" => "currencyIso",
            "CustomerOrderBillingAddress|addBillingAddress|true" => "billingAddress",
            "CustomerOrderShippingAddress|addShippingAddress|true" => "shippingAddress",
            "customers_address_format_id" => null,
            "billing_address_format_id" => null,
            "delivery_address_format_id" => null,
            "shipping_class" => "shippingMethodId",
            "shipping_method" => "shippingMethodName",
            "CustomerOrderItem|addItem" => "items",
        ],
    ];

    public function __construct(IDatabase $db, array $shopConfig, \stdClass $connectorConfig)
    {
        parent::__construct($db, $shopConfig, $connectorConfig);
        if (!empty($this->connectorConfig->from_date)) {
            $this->mapperConfig['query'] .= ' AND date_purchased >= "' . $this->connectorConfig->from_date . '"';
            $this->mapperConfig['statisticsQuery'] .= ' AND date_purchased >= "' . $this->connectorConfig->from_date . '"';
        }
    }

    public function pull($data = null, $limit = null): array
    {
        return parent::pull(null, $limit);
    }

    protected function status($data)
    {
        $defaultStatus = $this->configHelper->getDbConfigValue('DEFAULT_ORDERS_STATUS_ID');

        /*
        if (count($defaultStatus) > 0) {
            $defaultStatus = $defaultStatus[0]['configuration_value'];

            if ($data['orders_status'] == $defaultStatus) {
                $newStatus = $this->connectorConfig->mapping->pending;

                if (!is_null($newStatus)) {
                    $this->db->query('UPDATE orders SET orders_status='.$newStatus.' WHERE orders_id='.$data['orders_id']);

                    $orderHistory = new \stdClass();
                    $orderHistory->orders_id = $data['orders_id'];
                    $orderHistory->orders_status_id = $newStatus;
                    $orderHistory->date_added = date('Y-m-d H:i:s');

                    $this->db->insertRow($orderHistory, 'orders_status_history');

                    $data['orders_status'] = $newStatus;
                }
            }
        }
        */
        if ($data['orders_status'] === $defaultStatus) {
            return CustomerOrderModel::STATUS_NEW;
        }

        $mapping = array_search($data['orders_status'], (array)$this->connectorConfig->mapping);

        if ($mapping == 'canceled') {
            return CustomerOrderModel::STATUS_CANCELLED;
        } elseif ($mapping == 'completed' || $mapping == 'shipped') {
            return CustomerOrderModel::STATUS_SHIPPED;
        }
    }

    protected function paymentStatus($data)
    {
        $mapping = array_search($data['orders_status'], (array)$this->connectorConfig->mapping);

        if ($mapping == 'completed' || $mapping == 'paid') {
            return CustomerOrderModel::PAYMENT_STATUS_COMPLETED;
        }
    }

    protected function languageISO($data)
    {
        return $this->string2locale($data['language']);
    }

    protected function orders_status($data)
    {
        $newStatus = null;

        if ($data->getOrderStatus() == CustomerOrderModel::STATUS_CANCELLED) {
            $newStatus = 'canceled';
        } else {
            if ($data->getPaymentStatus() == CustomerOrderModel::PAYMENT_STATUS_COMPLETED && $data->getOrderStatus() == CustomerOrderModel::STATUS_SHIPPED) {
                $newStatus = 'completed';
            } else {
                if ($data->getOrderStatus() == CustomerOrderModel::STATUS_SHIPPED) {
                    $newStatus = 'shipped';
                } elseif ($data->getPaymentStatus() == CustomerOrderModel::PAYMENT_STATUS_COMPLETED) {
                    $newStatus = 'paid';
                }
            }
        }

        if (!is_null($newStatus)) {
            $mapping = (array)$this->connectorConfig->mapping;

            return $mapping[$newStatus];
        }
    }

    protected function paymentModuleCode($data)
    {
        return Payment::mapPaymentType(!empty($data['gambio_hub_module']) ? $data['gambio_hub_module'] : $data['payment_method']);
    }

    protected function payment_method($data)
    {
        return Payment::mapPaymentType($data->getPaymentModuleCode(), false);
    }

    protected function payment_class($data)
    {
        return Payment::mapPaymentType($data->getPaymentModuleCode(), false);
    }

    protected function customers_address_format_id($data)
    {
        return 5;
    }

    protected function billing_address_format_id($data)
    {
        return 5;
    }

    protected function delivery_address_format_id($data)
    {
        return 5;
    }

    public function push($data = null, $dbObj = null)
    {
        $id = $data->getId()->getEndpoint();

        if (!empty($id)) {
            $this->clear($data->getId()->getEndpoint());
        }

        $return = parent::push($data, $dbObj);

        $orderHistory = new \stdClass();
        $orderHistory->orders_id = $id;
        $orderHistory->orders_status_id = $this->orders_status($data);
        $orderHistory->date_added = date('Y-m-d H:i:s');

        $this->db->insertRow($orderHistory, 'orders_status_history');

        return $return;
    }

    public function clear($orderId)
    {
        $queries = [
            'DELETE FROM orders_total WHERE orders_id=' . $orderId,
            'DELETE FROM orders_products_attributes WHERE orders_id=' . $orderId,
            'DELETE FROM orders_products WHERE orders_id=' . $orderId,
            'DELETE FROM orders WHERE orders_id=' . $orderId,
        ];

        foreach ($queries as $query) {
            $this->db->query($query);
        }
    }

    /**
     * @param CustomerOrderModel $model
     * @param $data
     */
    public function addData($model, $data)
    {
        $vat = static::determineDefaultTaxRate($this->db, $data['orders_id']);
        if ($vat === false) {
            $vat = 0.;
            $productQuery = sprintf('SELECT MAX(`products_tax`) `products_tax` FROM `orders_products` WHERE `orders_id` = %d', $data['orders_id']);
            $orderProducts = $this->db->query($productQuery);
            if (is_array($orderProducts) && isset($orderProducts[0]['products_tax'])) {
                $vat = (float)$orderProducts[0]['products_tax'];
            }
        }

        $totalData = $this->db->query(sprintf('SELECT `orders_total_id`, `class`, `value`, `title` FROM `orders_total` WHERE `orders_id` = %d', $data['orders_id']));
        foreach ($totalData as $total) {
            switch ($total['class']) {
                case 'ot_total':
                    $model->setTotalSumGross((float)($total['value']));
                    break;
                case 'ot_total_netto':
                case 'ot_subtotal_no_tax':
                    $model->setTotalSum((float)($total['value']));
                    break;
                case 'ot_shipping':
                case 'ot_gambioultra':
                    $this->addShipping($total, $data, $vat, $model);
                    break;
                case 'ot_cod_fee':
                    $this->addSpecialItem(CustomerOrderItem::TYPE_SHIPPING, $model, $total, $data, $vat);
                    break;
                case 'ot_payment':
                    $this->addSpecialItem(CustomerOrderItem::TYPE_PRODUCT, $model, $total, $data, $vat);
                    break;
                case 'ot_coupon':
                case 'ot_gv':
                case 'ot_discount':
                    $this->addSpecialItem(CustomerOrderItem::TYPE_COUPON, $model, $total, $data, $vat);
                    break;
            }
        }
    }

    /**
     * @param $db
     * @param int $ordersId
     * @return float
     */
    public static function determineDefaultTaxRate($db, $ordersId)
    {
        $sql = sprintf('SELECT MAX(`tax_rate`) `tax_rate` FROM `orders_tax_sum_items` WHERE `order_id` = %d', $ordersId);
        $taxRate = $db->query($sql);
        return isset($taxRate[0]['tax_rate']) ? (float)$taxRate[0]['tax_rate'] : false;
    }

    /**
     * @param $type
     * @param $model
     * @param $total
     * @param $data
     * @param $vat
     */
    protected function addSpecialItem($type, $model, $total, $data, $vat)
    {
        $item = (new CustomerOrderItem())
            ->setType($type)
            ->setName($total['title'])
            ->setCustomerOrderId($this->identity($data['orders_id']))
            ->setId($this->identity($total['orders_total_id']))
            ->setQuantity(1)
            ->setVat($vat)
            ->setPriceGross($total['class'] === 'ot_gv' ? floatval($total['value']) * -1 : floatval($total['value']));

        if ($vat === 0.) {
            $item->setPrice($item->getPriceGross());
        }

        $model->addItem($item);
    }

    /**
     * @param $total
     * @param $data
     * @param $vat
     * @param $model
     */
    protected function addShipping($total, $data, $vat, $model)
    {
        $shipping = (new CustomerOrderItem())
            ->setType(CustomerOrderItem::TYPE_SHIPPING)
            ->setCustomerOrderId($this->identity($data['orders_id']))
            ->setId($this->identity($data['shipping_class']))
            ->setQuantity(1)
            ->setVat(0);

        $price = (float)$total['value'];

        list($shippingModule, $shippingName) = explode('_', $data['shipping_class']);

        $moduleTaxClass = $this->configHelper->getDbConfigValue('MODULE_SHIPPING_' . strtoupper($shippingModule) . '_TAX_CLASS');
        if ($vat !== 0. && !empty($moduleTaxClass) && !empty($data['delivery_country_iso_code_2'])) {
            $rateResult = $this->db->query('SELECT r.tax_rate FROM countries c
                          LEFT JOIN zones_to_geo_zones z ON z.zone_country_id = c.countries_id
                          LEFT JOIN tax_rates r ON r.tax_zone_id = z.geo_zone_id
                          WHERE c.countries_iso_code_2 = "' . $data['delivery_country_iso_code_2'] . '" && r.tax_class_id=' . $moduleTaxClass);

            if (count($rateResult) > 0 && isset($rateResult[0]['tax_rate'])) {
                $vat = floatval($rateResult[0]['tax_rate']);
            }
        }

        $shipping->setPriceGross($price);
        if ($vat === 0.) {
            $shipping->setPrice($price);
        }

        $shipping->setVat($vat);
        $shipping->setName($total['title']);

        $model->setShippingMethodName($total['title']);
        $model->addItem($shipping);
    }
}
