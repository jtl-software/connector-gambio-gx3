<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\AbstractMapper;
use jtl\Connector\Model\DataModel;
use jtl\Connector\Model\ProductPrice as ProductPriceModel;
use jtl\Connector\Model\ProductPriceItem as ProductPriceItemModel;

class ProductPrice extends AbstractMapper
{
    protected $mapperConfig = [
        "getMethod" => "getPrices",
        "mapPull" => [
            "id" => null,
            "customerGroupId" => "customers_status_id",
            "productId" => "products_id",
            "items" => "ProductPriceItem|addItem"
        ],
        "mapPush" => [
            "ProductPriceItem|addItem" => "items"
        ]
    ];

    public function pull($data = null, $limit = null): array
    {
        $customerGroups = $this->getCustomerGroups();

        $return = [];

        foreach ($customerGroups as $groupData) {
            $groupData['products_id'] = $data['products_id'];

            $price = $this->generateModel($groupData);

            if (count($price->getItems()) > 0) {
                $return[] = $price;
            }
        }

        $default = new ProductPriceModel();
        $default->setId($this->identity($data['products_id'].'_default'));
        $default->setProductId($this->identity($data['products_id']));
        $default->setCustomerGroupId($this->identity(''));

        $defaultItem = new ProductPriceItemModel();
        $defaultItem->setProductPriceId($default->getId());
        $defaultItem->setNetPrice(floatval($data['products_price']));

        $default->addItem($defaultItem);

        $return[] = $default;

        return $return;
    }

    public function push(DataModel $model, \stdClass $dbObj = null)
    {
        if (get_class($model) == 'jtl\Connector\Model\Product') {
            $productId = $model->getId();

            foreach ($model->getPrices() as $price) {
                $price->setProductId($productId);
            }
        } else {
            $productId = $model->getProductId()->getEndpoint();

            if (strpos($productId, '_') == false) {
                $customerGrp = $model->getCustomerGroupId()->getEndpoint();

                if (!is_null($customerGrp) && $customerGrp != '' && !empty($productId)) {
                    $this->db->query(sprintf('DELETE FROM personal_offers_by_customers_status_%s WHERE products_id=%s', $customerGrp, $productId));
                }
            }

            unset($this->mapperConfig['getMethod']);
        }

        return parent::push($model, $dbObj);
    }

    protected function id($data)
    {
        return $data['products_id'].'_'.$data['customers_status_id'];
    }
}
