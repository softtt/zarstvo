<?php

class OrderDetail extends OrderDetailCore
{
    public static function getCrossSells($id_product, $id_lang, $limit = 12)
    {
        if (!$id_product || !$id_lang)
            return;

        $front = true;
        if (!in_array(Context::getContext()->controller->controller_type, array('front', 'modulefront')))
            $front = false;

        $orders = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
        SELECT o.id_order
        FROM '._DB_PREFIX_.'orders o
        LEFT JOIN '._DB_PREFIX_.'order_detail od ON (od.id_order = o.id_order)
        WHERE o.valid = 1 AND od.product_id = '.(int)$id_product);

        $order_filter = '';
        if (count($orders))
        {
            $list = '';
            foreach ($orders as $order)
                $list .= (int)$order['id_order'].',';
            $list = rtrim($list, ',');
            $order_filter = 'od.id_order IN ('.$list.') AND';
        }

        $order_products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT DISTINCT od.product_id, p.id_product, pl.name, pl.link_rewrite, p.reference, i.id_image, product_shop.show_price,
            cl.link_rewrite category, p.ean13, p.out_of_stock, p.id_category_default, stock.quantity
            FROM '._DB_PREFIX_.'order_detail od
            LEFT JOIN '._DB_PREFIX_.'product p ON (p.id_product = od.product_id)
            '.Shop::addSqlAssociation('product', 'p').'
            '.Product::sqlStock('p', 0).'
            LEFT JOIN '._DB_PREFIX_.'product_lang pl ON (pl.id_product = od.product_id'.Shop::addSqlRestrictionOnLang('pl').')
            LEFT JOIN '._DB_PREFIX_.'category_lang cl ON (cl.id_category = product_shop.id_category_default'.Shop::addSqlRestrictionOnLang('cl').')
            LEFT JOIN '._DB_PREFIX_.'image i ON (i.id_product = od.product_id)
            WHERE '.$order_filter.'
                pl.id_lang = '.(int)$id_lang.'
                AND cl.id_lang = '.(int)$id_lang.'
                AND od.product_id != '.(int)$id_product.'
                AND i.cover = 1
                AND stock.quantity > 0
                AND product_shop.active = 1'
                .($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').'
            ORDER BY RAND()
            LIMIT '.(int)$limit

        );

        $tax_calc = Product::getTaxCalculationMethod();
        if (is_array($order_products))
        {
            foreach ($order_products as &$order_product)
            {
                $order_product['image'] = Context::getContext()->link->getImageLink($order_product['link_rewrite'],
                    (int)$order_product['product_id'].'-'.(int)$order_product['id_image'], ImageType::getFormatedName('medium'));
                $order_product['link'] = Context::getContext()->link->getProductLink((int)$order_product['product_id'],
                    $order_product['link_rewrite'], $order_product['category'], $order_product['ean13']);
                if ($tax_calc == 0 || $tax_calc == 2)
                    $order_product['displayed_price'] = Product::getPriceStatic((int)$order_product['product_id'], true, null);
                elseif ($tax_calc == 1)
                    $order_product['displayed_price'] = Product::getPriceStatic((int)$order_product['product_id'], false, null);
                $order_product['available_for_order'] = (int)1;
            }
            return Product::getProductsProperties($id_lang, $order_products);
        }
    }


    /**
     * Set detailed product price to the order detail
     * @param object $order
     * @param object $cart
     * @param array $product
     */
    protected function setDetailProductPrice(Order $order, Cart $cart, $product)
    {
        $this->setContext((int)$product['id_shop']);
        Product::getPriceStatic((int)$product['id_product'], true, (int)$product['id_product_attribute'], 6, null, false, true, $product['cart_quantity'], false, (int)$order->id_customer, (int)$order->id_cart, (int)$order->{Configuration::get('PS_TAX_ADDRESS_TYPE')}, $specific_price, true, true, $this->context);
        $this->specificPrice = $specific_price;
        $this->original_product_price = Product::getPriceStatic($product['id_product'], false, (int)$product['id_product_attribute'], 6, null, false, false, 1, false, null, null, null, $null, true, true, $this->context);
        $this->product_price = $this->original_product_price;
        $this->unit_price_tax_incl = (float)$product['price_wt'];
        $this->unit_price_tax_excl = (float)$product['price'];
        $this->total_price_tax_incl = (float)$product['total_wt'];
        $this->total_price_tax_excl = (float)$product['total'];

        $this->purchase_supplier_price = (float)$product['wholesale_price'];
        if ($product['id_supplier'] > 0 && ($supplier_price = ProductSupplier::getProductPrice((int)$product['id_supplier'], $product['id_product'], $product['id_product_attribute'], true)) > 0)
            $this->purchase_supplier_price = (float)$supplier_price;

        $this->setSpecificPrice($order, $product);

        $this->group_reduction = (float)Group::getReduction((int)$order->id_customer);

        $shop_id = $this->context->shop->id;

        $quantity_discount = SpecificPrice::getQuantityDiscount((int)$product['id_product'], $shop_id,
        (int)$cart->id_currency, (int)$this->vat_address->id_country,
        (int)$this->customer->id_default_group, (int)$product['cart_quantity'], false, null, null, $null, true, true, $this->context);

        $unit_price = Product::getPriceStatic((int)$product['id_product'], true,
            ($product['id_product_attribute'] ? intval($product['id_product_attribute']) : null),
            2, null, false, true, 1, false, (int)$order->id_customer, null, (int)$order->{Configuration::get('PS_TAX_ADDRESS_TYPE')}, $null, true, true, $this->context);
        $this->product_quantity_discount = 0.00;
        if ($quantity_discount)
        {
            $this->product_quantity_discount = $unit_price;
            if (Product::getTaxCalculationMethod((int)$order->id_customer) == PS_TAX_EXC)
                $this->product_quantity_discount = Tools::ps_round($unit_price, 2);

            // if (isset($this->tax_calculator))
            //     $this->product_quantity_discount -= $this->tax_calculator->addTaxes($quantity_discount['price']);
        }

        $this->discount_quantity_applied = (($this->specificPrice && $this->specificPrice['from_quantity'] > 1) ? 1 : 0);
    }
}
