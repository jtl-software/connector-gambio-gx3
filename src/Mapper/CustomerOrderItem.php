<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Mapper\BaseMapper;

class CustomerOrderItem extends BaseMapper
{
    protected $mapperConfig = [
        "table" => "orders_products",
        "query" => "SELECT p.*,v.products_properties_combis_id FROM orders_products p LEFT JOIN (SELECT orders_products_id, products_properties_combis_id FROM orders_products_properties GROUP BY orders_products_id, products_properties_combis_id) v ON v.orders_products_id=p.orders_products_id WHERE p.orders_id=[[orders_id]]",
        "where" => "orders_products_id",
        "getMethod" => "getItems",
        "identity" => "getId",
        "mapPull" => [
            "id" => "orders_products_id",
            "productId" => null,
            "customerOrderId" => "orders_id",
            "quantity" => "products_quantity",
            "name" => "products_name",
            "price" => null,
            "priceGross" => null,
            "vat" => null,
            "sku" => "products_model",
            "variations" => "CustomerOrderItemVariation|addVariation",
            "type" => null
        ],
        "mapPush" => [
            "orders_products_id" => "id",
            "products_id" => "productId",
            "orders_id" => null,
            "products_quantity" => "quantity",
            "products_name" => "name",
            "products_price" => null,
            "products_tax" => "vat",
            "products_model" => "sku",
            "allow_tax" => null,
            "final_price" => null,
            "CustomerOrderItemVariation|addVariation" => "variations"
        ]
    ];

    public function addData($model, $data)
    {
        $append = [];

        foreach ($model->getVariations() as $variation) {
            $append[] = $variation->getValueName();
        }

        if (count($append) > 0) {
            $model->setName($model->getName() . ' (' . implode(', ', $append) . ')');
        }
    }

    public function push($parent, $dbObj = null)
    {
        $return = [];

        $shippingCosts = 0;
        $sum = 0;
        $taxes = 0;

        foreach ($parent->getItems() as $itemData) {
            if ($itemData->getType() == "product") {
                $return[] = $this->generateDbObj($itemData, $dbObj, $parent);
                $tax = ($itemData->getPrice() / 100) * $itemData->getVat();
                $taxes += $tax;
                $sum += $itemData->getPrice() + $tax;
            } elseif ($itemData->getType() == "shipping") {
                $shippingCosts += $itemData->getPrice();
                $tax = ($itemData->getPrice() / 100) * $itemData->getVat();
                $taxes += $tax;
            }
        }

        $totals = [];

        $ot_shipping = new \stdClass();
        $ot_shipping->title = $parent->getShippingMethodName() . ':';
        $ot_shipping->text = number_format($shippingCosts, 2, ',', '.') . ' ' . $parent->getCurrencyIso();
        $ot_shipping->value = $shippingCosts;
        $ot_shipping->sort_order = 30;
        $ot_shipping->class = 'ot_shipping';
        $totals[] = $ot_shipping;

        $ot_subtotal = new \stdClass();
        $ot_subtotal->title = 'Zwischensumme:';
        $ot_subtotal->text = number_format($sum, 2, ',', '.') . ' ' . $parent->getCurrencyIso();
        $ot_subtotal->value = $sum;
        $ot_subtotal->sort_order = 10;
        $ot_subtotal->class = 'ot_subtotal';
        $totals[] = $ot_subtotal;

        $ot_total = new \stdClass();
        $ot_total->title = '<b>Summe</b>:';
        $ot_total->text = '<b> ' . number_format($sum + $shippingCosts, 2, ',', '.') . ' ' . $parent->getCurrencyIso() . '</b>';
        $ot_total->value = $sum + $shippingCosts;
        $ot_total->sort_order = 99;
        $ot_total->class = 'ot_total';
        $totals[] = $ot_total;

        $ot_tax = new \stdClass();
        $ot_tax->title = 'Steuer:';
        $ot_tax->text = number_format($taxes, 2, ',', '.') . ' ' . $parent->getCurrencyIso();
        $ot_tax->value = $taxes;
        $ot_tax->sort_order = 30;
        $ot_tax->class = 'ot_tax';
        $totals[] = $ot_tax;

        foreach ($totals as $total) {
            $total->orders_id = $parent->getId()->getEndpoint();
            $this->db->deleteInsertRow($total, 'orders_total', ['orders_id', 'class'], [$parent->getId()->getEndpoint(), $total->class]);
        }

        return $return;
    }

    protected function productId($data)
    {
        if (!empty($data['products_properties_combis_id'])) {
            return $data['products_id'] . '_' . $data['products_properties_combis_id'];
        }

        return $data['products_id'];
    }

    protected function price($data)
    {
        return $data['allow_tax'] === '1' ? $data['products_price'] / (100 + $data['products_tax']) * 100 : $data['products_price'];
    }

    protected function priceGross($data)
    {
        return $data['allow_tax'] === '1' ? $data['products_price'] : $data['products_price'] * ($data['products_tax'] / 100 + 1);
    }

    protected function vat($data)
    {
        if (CustomerOrder::determineDefaultTaxRate($this->db, $data['orders_id']) === 0.) {
            return 0.;
        }
        return $data['products_tax'];
    }

    protected function products_price($data)
    {
        return ($data->getPrice() / 100) * (100 + $data->getVat());
    }

    protected function final_price($data)
    {
        return (($data->getPrice() / 100) * (100 + $data->getVat())) * $data->getQuantity();
    }

    protected function allow_tax($data)
    {
        return 1;
    }

    protected function orders_id($data, $model, $parent)
    {
        $data->setCustomerOrderId($parent->getId());

        return $parent->getId()->getEndpoint();
    }

    protected function type($data)
    {
        return 'product';
    }
}
