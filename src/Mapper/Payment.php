<?php

namespace jtl\Connector\Gambio\Mapper;

use \jtl\Connector\Model\Payment as PaymentModel;
use jtl\Connector\Payment\PaymentTypes;

class Payment extends \jtl\Connector\Gambio\Mapper\BaseMapper
{
    protected $mapperConfig = [
        "table"    => "jtl_connector_payment",
        "query"    => "SELECT p.* FROM jtl_connector_payment p
            LEFT JOIN jtl_connector_link_payment l ON p.id = l.endpoint_id
            WHERE l.host_id IS NULL",
        "where"    => "id",
        "identity" => "getId",
        "mapPull"  => [
            "id"                => "id",
            "customerOrderId"   => "customerOrderId",
            "billingInfo"       => "billingInfo",
            "creationDate"      => "creationDate",
            "totalSum"          => "totalSum",
            "transactionId"     => "transactionId",
            "paymentModuleCode" => "paymentModuleCode",
        ],
    ];
    
    private $paymentMapping = [
        'cash'                      => PaymentTypes::TYPE_CASH,
        'klarna_SpecCamp'           => PaymentTypes::TYPE_KLARNA,
        'klarna_invoice'            => PaymentTypes::TYPE_KLARNA,
        'klarna_partPayment'        => PaymentTypes::TYPE_KLARNA,
        'moneyorder'                => PaymentTypes::TYPE_PREPAYMENT,
        'banktransfer'              => PaymentTypes::TYPE_BANK_TRANSFER,
        'cod'                       => PaymentTypes::TYPE_CASH_ON_DELIVERY,
        //'paypal' => 'pm_paypal_standard',
        //'paypal_ipn' => 'pm_paypal_standard',
        //'paypalexpress' => 'pm_paypal_express',
        'paypal3'                   => PaymentTypes::TYPE_PAYPAL_PLUS,
        'amoneybookers'             => PaymentTypes::TYPE_SKRILL,
        'moneybookers_giropay'      => PaymentTypes::TYPE_SKRILL,
        'moneybookers_ideal'        => PaymentTypes::TYPE_SKRILL,
        'moneybookers_mae'          => PaymentTypes::TYPE_SKRILL,
        'moneybookers_netpay'       => PaymentTypes::TYPE_SKRILL,
        'moneybookers_psp'          => PaymentTypes::TYPE_SKRILL,
        'moneybookers_pwy'          => PaymentTypes::TYPE_SKRILL,
        'moneybookers_sft'          => PaymentTypes::TYPE_SKRILL,
        'moneybookers_wlt'          => PaymentTypes::TYPE_SKRILL,
        'invoice'                   => PaymentTypes::TYPE_INVOICE,
        'sofort_sofortueberweisung' => PaymentTypes::TYPE_SOFORT,
        'worldpay'                  => PaymentTypes::TYPE_WORLDPAY,
        //HUB TYPES
        'CashHub'                   => PaymentTypes::TYPE_CASH,
        'CashOnDeliveryHub'         => PaymentTypes::TYPE_CASH_ON_DELIVERY,
        'InvoiceHub'                => PaymentTypes::TYPE_INVOICE,
        'KlarnaPaylaterHub'         => PaymentTypes::TYPE_KLARNA,
        'KlarnaPaynowHub'           => PaymentTypes::TYPE_KLARNA,
        'KlarnaSliceitHub'          => PaymentTypes::TYPE_KLARNA,
        'KlarnaBanktrankferHub'     => PaymentTypes::TYPE_KLARNA,
        'MoneyOrderHub'             => PaymentTypes::TYPE_PREPAYMENT,
        'MoneyOrderPlusHub'         => PaymentTypes::TYPE_PREPAYMENT,
        'PayPalHub'                 => PaymentTypes::TYPE_PAYPAL_PLUS,
        'SofortHub'                 => PaymentTypes::TYPE_SOFORT,
        'WirecardCreditcardHub'     => PaymentTypes::TYPE_WIRECARD,
        'WirecardInvoiceHub'        => PaymentTypes::TYPE_WIRECARD,
        'WirecardSepaddHub'         => PaymentTypes::TYPE_WIRECARD,
        'WirecardSofortbankingHub'  => PaymentTypes::TYPE_WIRECARD,
        'WirecardWiretransferHub'   => PaymentTypes::TYPE_WIRECARD,
    ];
    
    public function __construct()
    {
        parent::__construct();
        
        if (!empty($this->connectorConfig->from_date)) {
            $this->mapperConfig['query'] .= ' && creationDate >= "' . $this->connectorConfig->from_date . '"';
        }
    }
    
    public function pull($parent = null, $limit = null)
    {
        $additional = [];
        
        $payments = $this->db->query('SELECT p.* FROM jtl_connector_payment p
            LEFT JOIN jtl_connector_link_payment l ON p.id = l.endpoint_id
            WHERE l.host_id IS NULL');
        
        foreach ($payments as $payment) {
            $additional[] = $this->generateModel($payment);
        }
        
        $result = array_merge(
            $this->paypal(),
            $this->hubPayments(),
            $additional
        );
        
        return $result;
    }
    
    public function statistic()
    {
        return count($this->pull());
    }
    
    private function paypal()
    {
        $return = [];

        $sql = "
            SELECT p.orders_id order_id, p.payment_id transaction_id, o.date_purchased creation_date, o.payment_class payment_module, t.value total_sum
            FROM orders_paypal_payments p
            LEFT JOIN jtl_connector_link_payment l ON p.payment_id = l.endpoint_id
            LEFT JOIN orders o ON o.orders_id = p.orders_id
            LEFT JOIN orders_total t ON t.orders_id = p.orders_id AND t.class = 'ot_total'
        ";
        
        $results = $this->db->query($sql);
        
        foreach ($results as $paymentData) {
            $payment = new PaymentModel();
            $payment->setCreationDate(new \DateTime($paymentData['creation_date']));
            $payment->setCustomerOrderId($this->identity($paymentData['order_id']));
            $payment->setId($this->identity($paymentData['transaction_id']));
            $payment->setPaymentModuleCode($paymentData['payment_module']);
            $payment->setTotalSum(floatval($paymentData['total_sum']));
            $payment->setTransactionId($paymentData['transaction_id']);
            
            $return[] = $payment;
        }
        
        return $return;
    }
    
    private function hubPayments()
    {
        $return = [];
        
        $results = $this->db->query('SELECT *
            FROM orders o
              LEFT JOIN orders_total ot ON o.orders_id = ot.orders_id
              LEFT JOIN jtl_connector_link_payment l ON o.gambio_hub_transaction_code = l.endpoint_id
            WHERE l.host_id IS NULL AND o.payment_method = "gambio_hub" AND ot.class = "ot_total"
            ');
            
        foreach ($results as $paymentData) {
            $payment = new PaymentModel();
            $payment->setCreationDate(new \DateTime($paymentData['date_purchased']));
            $payment->setCustomerOrderId($this->identity($paymentData['orders_id']));
            $payment->setId($this->identity($paymentData['gambio_hub_transaction_code']));
            $payment->setPaymentModuleCode(
                isset($this->paymentMapping[$paymentData['gambio_hub_module']])
                ? $this->paymentMapping[$paymentData['gambio_hub_module']]
                : $paymentData['gambio_hub_module']
            );
            $payment->setTotalSum(floatval($paymentData['value']));
            $payment->setTransactionId($paymentData['gambio_hub_transaction_code']);
            
            $return[] = $payment;
        }
        
        return $return;
    }
}
