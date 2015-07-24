<?php
namespace jtl\Connector\Gambio\Mapper;

use \jtl\Connector\Model\Payment as PaymentModel;

class Payment extends \jtl\Connector\Gambio\Mapper\BaseMapper
{
    protected $mapperConfig = array(
        "table" => "jtl_connector_payment",
        "query" => "SELECT p.* FROM jtl_connector_payment p
            LEFT JOIN jtl_connector_link l ON p.id = l.endpointId AND l.type = 512
            WHERE l.hostId IS NULL",
        "where" => "id",
        "identity" => "getId",
        "mapPull" => array(
            "id" => "id",
            "customerOrderId" => "customerOrderId",
            "billingInfo" => "billingInfo",
            "creationDate" => "creationDate",
            "totalSum" => "totalSum",
            "transactionId" => "transactionId",
            "paymentModuleCode" => "paymentModuleCode"
        )
    );

    public function pull($parent = null, $limit = null)
    {
        $additional = array();

        $payments = $this->db->query('SELECT p.* FROM jtl_connector_payment p
            LEFT JOIN jtl_connector_link l ON p.id = l.endpointId AND l.type = 512
            WHERE l.hostId IS NULL');

        foreach ($payments as $payment) {
            $additional[] = $this->generateModel($payment);
        }

        $result = array_merge(
            $this->paypal(),
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
        $return = array();

        $results = $this->db->query('SELECT p.orders_id, p.transaction_id, p.payment_date, p.grossamount
          FROM paypal_transactions p
          LEFT JOIN jtl_connector_link l ON p.transaction_id = l.endpointId COLLATE utf8_unicode_ci AND l.type = 512
          WHERE l.hostId IS NULL && p.paymentstatus="Completed"');

        foreach ($results as $paymentData) {
            $payment = new PaymentModel();
            $payment->setCreationDate(new \DateTime($paymentData['payment_date']));
            $payment->setCustomerOrderId($this->identity($paymentData['orders_id']));
            $payment->setId($this->identity($paymentData['transaction_id']));
            $payment->setPaymentModuleCode('pm_paypal_standard');
            $payment->setTotalSum(floatval($paymentData['grossamount']));
            $payment->setTransactionId($paymentData['transaction_id']);

            $return[] = $payment;
        }

        return $return;
    }
}
