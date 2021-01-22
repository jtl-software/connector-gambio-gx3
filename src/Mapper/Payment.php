<?php

namespace jtl\Connector\Gambio\Mapper;

use \jtl\Connector\Model\Payment as PaymentModel;
use jtl\Connector\Payment\PaymentTypes;

class Payment extends \jtl\Connector\Gambio\Mapper\BaseMapper
{
    protected $mapperConfig = [
        "table" => "jtl_connector_payment",
        "query" => "SELECT p.* FROM jtl_connector_payment p
            LEFT JOIN jtl_connector_link_payment l ON p.id = l.endpoint_id
            WHERE l.host_id IS NULL",
        "where" => "id",
        "identity" => "getId",
        "mapPull" => [
            "id" => "id",
            "customerOrderId" => "customerOrderId",
            "billingInfo" => "billingInfo",
            "creationDate" => "creationDate",
            "totalSum" => "totalSum",
            "transactionId" => "transactionId",
            "paymentModuleCode" => "paymentModuleCode",
        ],
    ];

    private static $paymentMapping = [
        'cash' => PaymentTypes::TYPE_CASH,
        'klarna_SpecCamp' => PaymentTypes::TYPE_KLARNA,
        'klarna_invoice' => PaymentTypes::TYPE_KLARNA,
        'klarna_partPayment' => PaymentTypes::TYPE_KLARNA,
        'moneyorder' => PaymentTypes::TYPE_PREPAYMENT,
        'banktransfer' => PaymentTypes::TYPE_BANK_TRANSFER,
        'cod' => PaymentTypes::TYPE_CASH_ON_DELIVERY,
        'paypal3' => PaymentTypes::TYPE_PAYPAL,
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
        'PayPal2Hub' => PaymentTypes::TYPE_PAYPAL_PLUS,
        'SofortHub' => PaymentTypes::TYPE_SOFORT,
        'WirecardCreditcardHub' => PaymentTypes::TYPE_WIRECARD,
        'WirecardInvoiceHub' => PaymentTypes::TYPE_WIRECARD,
        'WirecardSepaddHub' => PaymentTypes::TYPE_WIRECARD,
        'WirecardSofortbankingHub' => PaymentTypes::TYPE_WIRECARD,
        'WirecardWiretransferHub' => PaymentTypes::TYPE_WIRECARD,
    ];

    /**
     * Payment constructor.
     */
    public function __construct()
    {
        parent::__construct();

        if (!empty($this->connectorConfig->from_date)) {
            $this->mapperConfig['query'] .= ' && creationDate >= "' . $this->connectorConfig->from_date . '"';
        }
    }

    /**
     * @param null $parent
     * @param null $limit
     * @return array
     * @throws \Exception
     */
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

    /**
     * @return int
     * @throws \Exception
     */
    public function statistic(): int
    {
        return count($this->pull());
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function paypal(): array
    {
        $return = [];

        $sql = 'SELECT o.orders_id, p.payment_id, o.date_purchased, o.payment_method, t.value
                FROM orders o
                LEFT JOIN orders_paypal_payments p ON o.orders_id = p.orders_id
                LEFT JOIN jtl_connector_link_payment l ON o.orders_id = l.endpoint_id
                LEFT JOIN orders_total t ON t.orders_id = p.orders_id AND t.class = \'ot_total\'
                WHERE l.host_id IS NULL
                GROUP BY o.orders_id';

        $results = $this->db->query($sql);

        foreach ($results as $paymentData) {
            $return[] = (new PaymentModel())
                ->setCreationDate(new \DateTime($paymentData['date_purchased']))
                ->setCustomerOrderId($this->identity($paymentData['orders_id']))
                ->setId($this->identity($paymentData['orders_id']))
                ->setPaymentModuleCode(self::mapPaymentType($paymentData['payment_method']))
                ->setTotalSum(floatval($paymentData['value']))
                ->setTransactionId($paymentData['payment_id']);
        }

        return $return;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function hubPayments(): array
    {
        $return = [];

        $sql = 'SELECT *
                FROM orders o
                LEFT JOIN orders_total ot ON o.orders_id = ot.orders_id AND ot.class = \'ot_total\'
                LEFT JOIN jtl_connector_link_payment l ON o.orders_id = l.endpoint_id
                WHERE l.host_id IS NULL AND o.payment_method = \'gambio_hub\'';

        $results = $this->db->query($sql);

        foreach ($results as $paymentData) {
            $return[] = (new PaymentModel())
                ->setCreationDate(new \DateTime($paymentData['date_purchased']))
                ->setCustomerOrderId($this->identity($paymentData['orders_id']))
                ->setId($this->identity($paymentData['orders_id']))
                ->setPaymentModuleCode(self::mapPaymentType($paymentData['gambio_hub_module']))
                ->setTotalSum(floatval($paymentData['value']))
                ->setTransactionId($paymentData['gambio_hub_transaction_code']);
        }

        return $return;
    }

    /**
     * @param string $moduleCode
     * @param bool $toJtl
     * @return string
     */
    public static function mapPaymentType(string $moduleCode, bool $toJtl = true): string
    {
        if ($toJtl === false) {
            return array_flip(self::$paymentMapping)[$moduleCode] ?? $moduleCode;
        }

        return self::$paymentMapping[$moduleCode] ?? $moduleCode;
    }
}
