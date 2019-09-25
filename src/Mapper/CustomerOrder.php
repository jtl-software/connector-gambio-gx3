<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Model\CustomerOrder as CustomerOrderModel;
use jtl\Connector\Payment\PaymentTypes;
use jtl\Connector\Model\CustomerOrderItem;

class CustomerOrder extends BaseMapper
{
    protected $mapperConfig = [
        "table" => "orders",
        "query" => "SELECT o.* FROM orders o
            LEFT JOIN jtl_connector_link_customer_order l ON o.orders_id = l.endpoint_id
            WHERE l.host_id IS NULL",
        "where" => "orders_id",
        "identity" => "getId",
        "mapPull" => [
            "id" => "orders_id",
            "orderNumber" => "orders_id",
            "customerId" => "customers_id",
            "creationDate" => "date_purchased",
            "note" => "comments",
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

    private $paymentMapping = [
        'cash' => PaymentTypes::TYPE_CASH,
        'klarna_SpecCamp' => PaymentTypes::TYPE_KLARNA,
        'klarna_invoice' => PaymentTypes::TYPE_KLARNA,
        'klarna_partPayment' => PaymentTypes::TYPE_KLARNA,
        'moneyorder' => PaymentTypes::TYPE_PREPAYMENT,
        'banktransfer' => PaymentTypes::TYPE_BANK_TRANSFER,
        'cod' => PaymentTypes::TYPE_CASH_ON_DELIVERY,
        //'paypal' => 'pm_paypal_standard',
        //'paypal_ipn' => 'pm_paypal_standard',
        //'paypalexpress' => 'pm_paypal_express',
        'paypal3' => PaymentTypes::TYPE_PAYPAL_PLUS,
        'amoneybookers' => PaymentTypes::TYPE_SKRILL,
        'moneybookers_giropay' => PaymentTypes::TYPE_SKRILL,
        'moneybookers_ideal' => PaymentTypes::TYPE_SKRILL,
        'moneybookers_mae' => PaymentTypes::TYPE_SKRILL,
        'moneybookers_netpay' => PaymentTypes::TYPE_SKRILL,
        'moneybookers_psp' => PaymentTypes::TYPE_SKRILL,
        'moneybookers_pwy' => PaymentTypes::TYPE_SKRILL,
        'moneybookers_sft' => PaymentTypes::TYPE_SKRILL,
        'moneybookers_wlt' => PaymentTypes::TYPE_SKRILL,
        'invoice' => PaymentTypes::TYPE_INVOICE,
        'sofort_sofortueberweisung' => PaymentTypes::TYPE_SOFORT,
        'worldpay' => PaymentTypes::TYPE_WORLDPAY,
        //HUB TYPES
        'CashHub' => PaymentTypes::TYPE_CASH,
        'CashOnDeliveryHub' => PaymentTypes::TYPE_CASH_ON_DELIVERY,
        'InvoiceHub' => PaymentTypes::TYPE_INVOICE,
        'KlarnaPaylaterHub' => PaymentTypes::TYPE_KLARNA,
        'KlarnaPaynowHub' => PaymentTypes::TYPE_KLARNA,
        'KlarnaSliceitHub' => PaymentTypes::TYPE_KLARNA,
        'KlarnaBanktrankferHub' => PaymentTypes::TYPE_KLARNA,
        'MoneyOrderHub' => PaymentTypes::TYPE_PREPAYMENT,
        'MoneyOrderPlusHub' => PaymentTypes::TYPE_PREPAYMENT,
        'PayPalHub' => PaymentTypes::TYPE_PAYPAL_PLUS,
        'SofortHub' => PaymentTypes::TYPE_SOFORT,
        'WirecardCreditcardHub' => PaymentTypes::TYPE_WIRECARD,
        'WirecardInvoiceHub' => PaymentTypes::TYPE_WIRECARD,
        'WirecardSepaddHub' => PaymentTypes::TYPE_WIRECARD,
        'WirecardSofortbankingHub' => PaymentTypes::TYPE_WIRECARD,
        'WirecardWiretransferHub' => PaymentTypes::TYPE_WIRECARD,
    ];

    public function __construct()
    {
        parent::__construct();

        if (!empty($this->connectorConfig->from_date)) {
            $this->mapperConfig['query'] .= ' && date_purchased >= "' . $this->connectorConfig->from_date . '"';
        }
    }

    public function pull($data = null, $limit = null)
    {
        return parent::pull(null, $limit);
    }

    protected function status($data)
    {
        $defaultStatus = $this->db->query('SELECT configuration_value FROM configuration WHERE configuration_key="DEFAULT_ORDERS_STATUS_ID"');
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
        if ($data['orders_status'] == $defaultStatus[0]['configuration_value']) {
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
        if (strcmp($data['payment_method'], 'gambio_hub') === 0) {

            if (key_exists($data['gambio_hub_module'], $this->paymentMapping)) {
                return $this->paymentMapping[$data['gambio_hub_module']];
            }

        } else {
            if (key_exists($data['payment_method'], $this->paymentMapping)) {
                return $this->paymentMapping[$data['payment_method']];
            }
        }

        return $data['payment_method'];
    }

    protected function payment_method($data)
    {
        $payments = array_flip($this->paymentMapping);

        return $payments[$data->getPaymentModuleCode()];
    }

    protected function payment_class($data)
    {
        $payments = array_flip($this->paymentMapping);

        return $payments[$data->getPaymentModuleCode()];
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

    public function addData($model, $data)
    {
        $shipping = new CustomerOrderItem();
        $shipping->setType('shipping');
        $shipping->setCustomerOrderId($this->identity($data['orders_id']));
        $shipping->setId($this->identity($data['shipping_class']));
        $shipping->setQuantity(1);
        $shipping->setVat(0);

        $totalData = $this->db->query('SELECT class,value,title FROM orders_total WHERE orders_id=' . $data['orders_id']);
        $taxRate = $this->db->query('SELECT tax_rate FROM orders_tax_sum_items WHERE order_id=' . $data['orders_id']);

        $vatExcl = !isset($taxRate[0]['tax_rate']) || (float)$taxRate[0]['tax_rate'] === 0.;
        foreach ($totalData as $total) {
            if ($total['class'] == 'ot_subtotal_no_tax') {
                $vatExcl = true;
                break;
            }
        }

        foreach ($totalData as $total) {
            if ($total['class'] == 'ot_total') {
                $model->setTotalSumGross(floatval($total['value']));
            }

            if ($total['class'] == 'ot_total_netto' || $total['class'] == 'ot_subtotal_no_tax') {
                $model->setTotalSum(floatval($total['value']));
            }

            if ($total['class'] == 'ot_shipping') {
                $vat = 0;
                $price = floatval($total['value']);

                list($shippingModule, $shippingName) = explode('_', $data['shipping_class']);

                $moduleTaxClass = $this->db->query('SELECT configuration_value FROM configuration WHERE configuration_key ="MODULE_SHIPPING_' . strtoupper($shippingModule) . '_TAX_CLASS"');
                if (!$vatExcl && count($moduleTaxClass) > 0) {
                    if (!empty($moduleTaxClass[0]['configuration_value']) && !empty($data['delivery_country_iso_code_2'])) {
                        $rateResult = $this->db->query('SELECT r.tax_rate FROM countries c
                          LEFT JOIN zones_to_geo_zones z ON z.zone_country_id = c.countries_id
                          LEFT JOIN tax_rates r ON r.tax_zone_id = z.geo_zone_id
                          WHERE c.countries_iso_code_2 = "' . $data['delivery_country_iso_code_2'] . '" && r.tax_class_id=' . $moduleTaxClass[0]['configuration_value']);

                        if (count($rateResult) > 0 && isset($rateResult[0]['tax_rate'])) {
                            $vat = floatval($rateResult[0]['tax_rate']);
                        }
                    }
                }

                $shipping->setPriceGross($price);
                if ($vatExcl) {
                    $shipping->setPrice($price);
                }

                $shipping->setVat($vat);
                $shipping->setName($total['title']);

                $model->setShippingMethodName($total['title']);
            }

            $specialItems = [
                'ot_cod_fee',
                'ot_payment',
                'ot_coupon',
                'ot_gv',
                'ot_discount'
            ];
            
            if (!array_search($total['class'], $specialItems)){
                continue;
            }

            $item = new CustomerOrderItem();
                switch ($total['class']) {
                    case 'ot_cod_fee':
                        $item->setType(CustomerOrderItem::TYPE_SHIPPING);
                        break;
        
                    case 'ot_payment':
                        $item->setType(CustomerOrderItem::TYPE_PRODUCT);
                        break;
        
                    case 'ot_coupon':
                    case 'ot_gv':
                    case 'ot_discount':
                        $item->setType(CustomerOrderItem::TYPE_COUPON);
                        break;
                }

                $item->setName($total['title']);
                $item->setCustomerOrderId($this->identity($data['orders_id']));
                $item->setId($this->identity($total['orders_total_id']));
                $item->setQuantity(1);
                $item->setVat(($vatExcl ? 0. : floatval($taxRate[0]['tax_rate'])));
                //$item->setPrice(floatval($total['value']) - (floatval($total['value'])*($taxRate[0]['tax_rate'] / 100)));
                $item->setPriceGross($total['class'] == 'ot_gv' ? floatval($total['value']) * -1 : floatval($total['value']));
    
                $model->addItem($item);
        }

        $model->addItem($shipping);
    }
}
