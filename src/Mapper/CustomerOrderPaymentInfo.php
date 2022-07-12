<?php

namespace jtl\Connector\Gambio\Mapper;

class CustomerOrderPaymentInfo extends AbstractMapper
{
    /**
     * @param $parentData
     * @param $limit
     * @return array
     */
    public function pull($parentData = null, $limit = null): array
    {
        $paymentInfo = null;
        $paymentInfoData = null;

        $isPaymentInstructionsInstalled = $this->db->query('SHOW TABLES LIKE "orders_payment_instruction"');
        if (is_array($isPaymentInstructionsInstalled) && count($isPaymentInstructionsInstalled) > 0) {
            $paymentInfoQuery = sprintf('SELECT `account_holder` AS `sepa_owner`, `iban` AS `sepa_iban`, orders_id, `bic` AS `sepa_bic`, `bank_name` AS `sepa_bankname` FROM `orders_payment_instruction` WHERE `orders_id` = %d',
                $parentData['orders_id']);
            $paymentInfoData = $this->db->query($paymentInfoQuery);
        }

        $isSepaInstalled = $this->db->query('SHOW TABLES LIKE "sepa"');
        if (empty($paymentInfoData) && is_array($isSepaInstalled) && count($isSepaInstalled) > 0) {
            $paymentInfoQuery = sprintf('SELECT * FROM `sepa` WHERE `orders_id` = %d', $parentData['orders_id']);
            $paymentInfoData = $this->db->query($paymentInfoQuery);
        }

        if (is_array($paymentInfoData) && isset($paymentInfoData[0]['orders_id'])) {
            $paymentInfoData = $paymentInfoData[0];
            $paymentInfo =
                (new \jtl\Connector\Model\CustomerOrderPaymentInfo())
                    ->setAccountHolder($paymentInfoData['sepa_owner'])
                    ->setIban($paymentInfoData['sepa_iban'])
                    ->setBic($paymentInfoData['sepa_bic'])
                    ->setBankName($paymentInfoData['sepa_bankname']);
        }

        return [$paymentInfo];
    }
}