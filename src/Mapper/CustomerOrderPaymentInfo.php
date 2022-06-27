<?php

namespace jtl\Connector\Gambio\Mapper;

class CustomerOrderPaymentInfo extends BaseMapper
{
    /**
     * @param $parentData
     * @param $limit
     * @return array|null
     */
    public function pull($parentData = null, $limit = null)
    {
        $isSepaInstalled = $this->db->query('SHOW TABLES LIKE "sepa"');
        $paymentInfo = null;
        if (is_array($isSepaInstalled) && count($isSepaInstalled) > 0) {

            $paymentInfoQuery = sprintf('SELECT * FROM `sepa` WHERE `orders_id` = %d', $parentData['orders_id']);
            $paymentInfoData = $this->db->query($paymentInfoQuery);

            if (is_array($paymentInfoData) && isset($paymentInfoData[0]['orders_id'])) {
                $paymentInfoData = $paymentInfoData[0];
                $paymentInfo = [
                    (new \jtl\Connector\Model\CustomerOrderPaymentInfo())
                        ->setAccountHolder($paymentInfoData['sepa_owner'])
                        ->setIban($paymentInfoData['sepa_iban'])
                        ->setBic($paymentInfoData['sepa_bic'])
                        ->setBankName($paymentInfoData['sepa_bankname'])
                ];
            }
        }

        return $paymentInfo;
    }
}