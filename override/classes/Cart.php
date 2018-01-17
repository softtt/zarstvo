<?php

class Cart extends CartCore
{
    public $copy_of_cart_id;
    public $calculated_delivery_cost;

    public static $definition = array(
        'table' => 'cart',
        'primary' => 'id_cart',
        'fields' => array(
            'id_shop_group' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_address_delivery' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_address_invoice' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_carrier' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_currency' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_guest' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_lang' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'recyclable' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'gift' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'gift_message' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage'),
            'mobile_theme' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'delivery_option' => array('type' => self::TYPE_STRING),
            'secure_key' => array('type' => self::TYPE_STRING, 'size' => 32),
            'allow_seperated_package' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),

            'copy_of_cart_id' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'calculated_delivery_cost' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
        ),
    );

    public function getCartRules($filter = CartRule::FILTER_ACTION_ALL)
    {
        // If the cart has not been saved, then there can't be any cart rule applied
        if (!CartRule::isFeatureActive() || !$this->id)
            return array();

        $result = Db::getInstance()->executeS('
            SELECT cr.*, crl.`id_lang`, crl.`name`, cd.`id_cart`
            FROM `' . _DB_PREFIX_ . 'cart_cart_rule` cd
            LEFT JOIN `' . _DB_PREFIX_ . 'cart_rule` cr ON cd.`id_cart_rule` = cr.`id_cart_rule`
            LEFT JOIN `' . _DB_PREFIX_ . 'cart_rule_lang` crl ON (
                cd.`id_cart_rule` = crl.`id_cart_rule`
                AND crl.id_lang = ' . (int)$this->id_lang . '
            )
            WHERE `id_cart` = ' . (int)$this->id . '
            ' . ($filter == CartRule::FILTER_ACTION_SHIPPING ? 'AND free_shipping = 1' : '') . '
            ' . ($filter == CartRule::FILTER_ACTION_GIFT ? 'AND gift_product != 0' : '') . '
            ' . ($filter == CartRule::FILTER_ACTION_REDUCTION ? 'AND (reduction_percent != 0 OR reduction_amount != 0)' : '')
            . ' ORDER by cr.priority ASC'
        );

        // Define virtual context to prevent case where the cart is not the in the global context
        $virtual_context = Context::getContext()->cloneContext();
        $virtual_context->cart = $this;

        foreach ($result as &$row) {
            $row['obj'] = new CartRule($row['id_cart_rule'], (int)$this->id_lang);
            $row['value_real'] = $row['obj']->getContextualValue(true, $virtual_context, $filter);
            $row['value_tax_exc'] = $row['obj']->getContextualValue(false, $virtual_context, $filter);

            // Retro compatibility < 1.5.0.2
            $row['id_discount'] = $row['id_cart_rule'];
            $row['description'] = $row['name'];
        }

        return $result;
    }

    /**
     * Return cart products
     *
     * @result array Products
     */
    public function getProducts($refresh = false, $id_product = false, $id_country = null)
    {
        if (!$this->id)
            return array();
        // Product cache must be strictly compared to NULL, or else an empty cart will add dozens of queries
        if ($this->_products !== null && !$refresh) {
            // Return product row with specified ID if it exists
            if (is_int($id_product)) {
                foreach ($this->_products as $product)
                    if ($product['id_product'] == $id_product)
                        return array($product);
                return array();
            }
            return $this->_products;
        }

        // Build query
        $sql = new DbQuery();

        // Build SELECT
        $sql->select('cp.`id_product_attribute`, cp.`id_product`, cp.`quantity` AS cart_quantity, cp.id_shop, pl.`name`, p.`is_virtual`,
                        pl.`description_short`, pl.`available_now`, pl.`available_later`, product_shop.`id_category_default`, p.`id_supplier`,
                        p.`id_manufacturer`, product_shop.`on_sale`, product_shop.`ecotax`, product_shop.`additional_shipping_cost`,
                        product_shop.`available_for_order`, product_shop.`price`, product_shop.`active`, product_shop.`unity`, product_shop.`unit_price_ratio`,
                        stock.`quantity` AS quantity_available, p.`width`, p.`height`, p.`depth`, stock.`out_of_stock`, p.`weight`,
                        p.`date_add`, p.`date_upd`, IFNULL(stock.quantity, 0) as quantity, pl.`link_rewrite`, cl.`link_rewrite` AS category,
                        CONCAT(LPAD(cp.`id_product`, 10, 0), LPAD(IFNULL(cp.`id_product_attribute`, 0), 10, 0), IFNULL(cp.`id_address_delivery`, 0)) AS unique_id, cp.id_address_delivery,
                        product_shop.advanced_stock_management, ps.product_supplier_reference supplier_reference, IFNULL(sp.`reduction_type`, 0) AS reduction_type');

        // Build FROM
        $sql->from('cart_product', 'cp');

        // Build JOIN
        $sql->leftJoin('product', 'p', 'p.`id_product` = cp.`id_product`');
        $sql->innerJoin('product_shop', 'product_shop', '(product_shop.`id_shop` = cp.`id_shop` AND product_shop.`id_product` = p.`id_product`)');
        $sql->leftJoin('product_lang', 'pl', '
            p.`id_product` = pl.`id_product`
            AND pl.`id_lang` = ' . (int)$this->id_lang . Shop::addSqlRestrictionOnLang('pl', 'cp.id_shop')
        );

        $sql->leftJoin('category_lang', 'cl', '
            product_shop.`id_category_default` = cl.`id_category`
            AND cl.`id_lang` = ' . (int)$this->id_lang . Shop::addSqlRestrictionOnLang('cl', 'cp.id_shop')
        );

        $sql->leftJoin('product_supplier', 'ps', 'ps.`id_product` = cp.`id_product` AND ps.`id_product_attribute` = cp.`id_product_attribute` AND ps.`id_supplier` = p.`id_supplier`');

        $sql->leftJoin('specific_price', 'sp', 'sp.`id_product` = cp.`id_product`'); // AND 'sp.`id_shop` = cp.`id_shop`

        // @todo test if everything is ok, then refactorise call of this method
        $sql->join(Product::sqlStock('cp', 'cp'));

        // Build WHERE clauses
        $sql->where('cp.`id_cart` = ' . (int)$this->id);
        if ($id_product)
            $sql->where('cp.`id_product` = ' . (int)$id_product);
        $sql->where('p.`id_product` IS NOT NULL');

        // Build GROUP BY
        $sql->groupBy('unique_id');

        // Build ORDER BY
        $sql->orderBy('cp.`id_cart_product` ASC');

        if (Customization::isFeatureActive()) {
            $sql->select('cu.`id_customization`, cu.`quantity` AS customization_quantity');
            $sql->leftJoin('customization', 'cu',
                'p.`id_product` = cu.`id_product` AND cp.`id_product_attribute` = cu.`id_product_attribute` AND cu.`id_cart` = ' . (int)$this->id);
        } else
            $sql->select('NULL AS customization_quantity, NULL AS id_customization');

        if (Combination::isFeatureActive()) {
            $sql->select('
                product_attribute_shop.`price` AS price_attribute, product_attribute_shop.`ecotax` AS ecotax_attr,
                IF (IFNULL(pa.`reference`, \'\') = \'\', p.`reference`, pa.`reference`) AS reference,
                (p.`weight`+ pa.`weight`) weight_attribute,
                IF (IFNULL(pa.`ean13`, \'\') = \'\', p.`ean13`, pa.`ean13`) AS ean13,
                IF (IFNULL(pa.`upc`, \'\') = \'\', p.`upc`, pa.`upc`) AS upc,
                pai.`id_image` as pai_id_image, il.`legend` as pai_legend,
                IFNULL(product_attribute_shop.`minimal_quantity`, product_shop.`minimal_quantity`) as minimal_quantity,
                IF(product_attribute_shop.wholesale_price > 0,  product_attribute_shop.wholesale_price, product_shop.`wholesale_price`) wholesale_price
            ');

            $sql->leftJoin('product_attribute', 'pa', 'pa.`id_product_attribute` = cp.`id_product_attribute`');
            $sql->leftJoin('product_attribute_shop', 'product_attribute_shop', '(product_attribute_shop.`id_shop` = cp.`id_shop` AND product_attribute_shop.`id_product_attribute` = pa.`id_product_attribute`)');
            $sql->leftJoin('product_attribute_image', 'pai', 'pai.`id_product_attribute` = pa.`id_product_attribute`');
            $sql->leftJoin('image_lang', 'il', 'il.`id_image` = pai.`id_image` AND il.`id_lang` = ' . (int)$this->id_lang);
        } else
            $sql->select(
                'p.`reference` AS reference, p.`ean13`,
                p.`upc` AS upc, product_shop.`minimal_quantity` AS minimal_quantity, product_shop.`wholesale_price` wholesale_price'
            );
        $result = Db::getInstance()->executeS($sql);

        // Reset the cache before the following return, or else an empty cart will add dozens of queries
        $products_ids = array();
        $pa_ids = array();
        if ($result)
            foreach ($result as $row) {
                $products_ids[] = $row['id_product'];
                $pa_ids[] = $row['id_product_attribute'];
            }
        // Thus you can avoid one query per product, because there will be only one query for all the products of the cart
        Product::cacheProductsFeatures($products_ids);
        Cart::cacheSomeAttributesLists($pa_ids, $this->id_lang);

        $this->_products = array();
        if (empty($result))
            return array();

        $cart_shop_context = Context::getContext()->cloneContext();
        foreach ($result as &$row) {
            if (isset($row['ecotax_attr']) && $row['ecotax_attr'] > 0)
                $row['ecotax'] = (float)$row['ecotax_attr'];

            $row['stock_quantity'] = (int)$row['quantity'];
            // for compatibility with 1.2 themes
            $row['quantity'] = (int)$row['cart_quantity'];

            if (isset($row['id_product_attribute']) && (int)$row['id_product_attribute'] && isset($row['weight_attribute']))
                $row['weight'] = (float)$row['weight_attribute'];

            if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice')
                $address_id = (int)$this->id_address_invoice;
            else
                $address_id = (int)$row['id_address_delivery'];
            if (!Address::addressExists($address_id))
                $address_id = null;

            if ($cart_shop_context->shop->id != $row['id_shop'])
                $cart_shop_context->shop = new Shop((int)$row['id_shop']);

            $address = Address::initialize($address_id, true);
            $id_tax_rules_group = Product::getIdTaxRulesGroupByIdProduct((int)$row['id_product'], $cart_shop_context);
            $tax_calculator = TaxManagerFactory::getManager($address, $id_tax_rules_group)->getTaxCalculator();

            $row['price'] = Product::getPriceStatic(
                (int)$row['id_product'],
                false,
                isset($row['id_product_attribute']) ? (int)$row['id_product_attribute'] : null,
                6,
                null,
                false,
                true,
                $row['cart_quantity'],
                false,
                (int)$this->id_customer ? (int)$this->id_customer : null,
                (int)$this->id,
                $address_id,
                $specific_price_output,
                false,
                true,
                $cart_shop_context
            );

            switch (Configuration::get('PS_ROUND_TYPE')) {
                case Order::ROUND_TOTAL:
                case Order::ROUND_LINE:
                    $row['total'] = Tools::ps_round($row['price'] * (int)$row['cart_quantity'], _PS_PRICE_COMPUTE_PRECISION_);
                    $row['total_wt'] = Tools::ps_round($tax_calculator->addTaxes($row['price']) * (int)$row['cart_quantity'], _PS_PRICE_COMPUTE_PRECISION_);
                    break;

                case Order::ROUND_ITEM:
                default:
                    $row['total'] = Tools::ps_round($row['price'], _PS_PRICE_COMPUTE_PRECISION_) * (int)$row['cart_quantity'];
                    $row['total_wt'] = Tools::ps_round($tax_calculator->addTaxes($row['price']), _PS_PRICE_COMPUTE_PRECISION_) * (int)$row['cart_quantity'];
                    break;
            }
            $row['price_wt'] = $tax_calculator->addTaxes($row['price']);
            $row['description_short'] = Tools::nl2br($row['description_short']);

            if (!isset($row['pai_id_image']) || $row['pai_id_image'] == 0) {
                $cache_id = 'Cart::getProducts_' . '-pai_id_image-' . (int)$row['id_product'] . '-' . (int)$this->id_lang . '-' . (int)$row['id_shop'];
                if (!Cache::isStored($cache_id)) {
                    $row2 = Db::getInstance()->getRow('
                        SELECT image_shop.`id_image` id_image, il.`legend`
                        FROM `' . _DB_PREFIX_ . 'image` i
                        JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop ON (i.id_image = image_shop.id_image AND image_shop.cover=1 AND image_shop.id_shop=' . (int)$row['id_shop'] . ')
                        LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int)$this->id_lang . ')
                        WHERE i.`id_product` = ' . (int)$row['id_product'] . ' AND image_shop.`cover` = 1'
                    );
                    Cache::store($cache_id, $row2);
                }
                $row2 = Cache::retrieve($cache_id);
                if (!$row2)
                    $row2 = array('id_image' => false, 'legend' => false);
                else
                    $row = array_merge($row, $row2);
            } else {
                $row['id_image'] = $row['pai_id_image'];
                $row['legend'] = $row['pai_legend'];
            }

            $row['reduction_applies'] = ($specific_price_output && (float)$specific_price_output['reduction']);
            $row['quantity_discount_applies'] = ($specific_price_output && $row['cart_quantity'] >= (int)$specific_price_output['from_quantity']);
            $row['id_image'] = Product::defineProductImage($row, $this->id_lang);
            $row['allow_oosp'] = Product::isAvailableWhenOutOfStock($row['out_of_stock']);
            $row['features'] = Product::getFeaturesStatic((int)$row['id_product']);

            if (array_key_exists($row['id_product_attribute'] . '-' . $this->id_lang, self::$_attributesLists))
                $row = array_merge($row, self::$_attributesLists[$row['id_product_attribute'] . '-' . $this->id_lang]);

            $row = Product::getTaxesInformations($row, $cart_shop_context);

            $this->_products[] = $row;
        }

        return $this->_products;
    }

    public function checkZeroQuantities()
    {
        $res = false;
        if (!$this->_products)
            $this->getProducts();
        foreach ($this->_products as $product) {
            $row = Db::getInstance()->getRow('
            SELECT `quantity`, `id_product` 
            FROM `' . _DB_PREFIX_ . 'stock_available`
            WHERE `id_product` = ' . (int)$product['id_product']
            );

            if ($row) {
                if ($row['quantity'] == 0) {
                    $this->deleteProduct($row['id_product']);
                    $res = true;
                }
            }

        }
        return $res;
    }

    /**
     * Return useful informations for cart
     *
     * @return array Cart details
     */
    public function getSummaryDetails($id_lang = null, $refresh = false)
    {
        $context = Context::getContext();
        if (!$id_lang)
            $id_lang = $context->language->id;

        $delivery = new Address((int)$this->id_address_delivery);
        $invoice = new Address((int)$this->id_address_invoice);

        // New layout system with personalization fields
        $formatted_addresses = array(
            'delivery' => AddressFormat::getFormattedLayoutData($delivery),
            'invoice' => AddressFormat::getFormattedLayoutData($invoice)
        );

        $base_total_tax_inc = $this->getOrderTotal(false);
        $base_total_tax_exc = $base_total_tax_inc;

        // $total_tax = $base_total_tax_inc - $base_total_tax_exc;

        // if ($total_tax < 0)
        //     $total_tax = 0;

        $currency = new Currency($this->id_currency);

        $products = $this->getProducts($refresh);
        $gift_products = array();
        $cart_rules = $this->getCartRules();
        $total_shipping = $this->getTotalShippingCost();
        $total_shipping_tax_exc = $this->getTotalShippingCost(null, false);
        $total_products_wt = $this->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $total_products = $this->getOrderTotal(false, Cart::ONLY_PRODUCTS);
        $total_discounts = $this->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        $total_discounts_tax_exc = $this->getOrderTotal(false, Cart::ONLY_DISCOUNTS);

        // The cart content is altered for display
        foreach ($cart_rules as &$cart_rule) {

            // If the cart rule is automatic (wihtout any code) and include free shipping, it should not be displayed as a cart rule but only set the shipping cost to 0
            // if ($cart_rule['free_shipping'] && (empty($cart_rule['code']) || preg_match('/^'.CartRule::BO_ORDER_CODE_PREFIX.'[0-9]+/', $cart_rule['code'])))
            // {
            //     $cart_rule['value_real'] -= $total_shipping;
            //     $cart_rule['value_tax_exc'] -= $total_shipping_tax_exc;
            //     $cart_rule['value_real'] = Tools::ps_round($cart_rule['value_real'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
            //     $cart_rule['value_tax_exc'] = Tools::ps_round($cart_rule['value_tax_exc'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
            //     if ($total_discounts > $cart_rule['value_real'])
            //         $total_discounts -= $total_shipping;
            //     if ($total_discounts_tax_exc > $cart_rule['value_tax_exc'])
            //         $total_discounts_tax_exc -= $total_shipping_tax_exc;

            //     // Update total shipping
            //     $total_shipping = 0;
            //     $total_shipping_tax_exc = 0;
            // }

            if ($cart_rule['gift_product']) {
                foreach ($products as $key => &$product)
                    if (empty($product['gift']) && $product['id_product'] == $cart_rule['gift_product'] && $product['id_product_attribute'] == $cart_rule['gift_product_attribute']) {
                        // Update total products
                        $total_products_wt = Tools::ps_round($total_products_wt - $product['price_wt'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $total_products = Tools::ps_round($total_products - $product['price'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);

                        // Update total discounts
                        $total_discounts = Tools::ps_round($total_discounts - $product['price_wt'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $total_discounts_tax_exc = Tools::ps_round($total_discounts_tax_exc - $product['price'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);

                        // Update cart rule value
                        $cart_rule['value_real'] = Tools::ps_round($cart_rule['value_real'] - $product['price_wt'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $cart_rule['value_tax_exc'] = Tools::ps_round($cart_rule['value_tax_exc'] - $product['price'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);

                        // Update product quantity
                        $product['total_wt'] = Tools::ps_round($product['total_wt'] - $product['price_wt'], (int)$currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $product['total'] = Tools::ps_round($product['total'] - $product['price'], (int)$currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $product['cart_quantity']--;

                        if (!$product['cart_quantity'])
                            unset($products[$key]);

                        // Add a new product line
                        $gift_product = $product;
                        $gift_product['cart_quantity'] = 1;
                        $gift_product['price'] = 0;
                        $gift_product['price_wt'] = 0;
                        $gift_product['total_wt'] = 0;
                        $gift_product['total'] = 0;
                        $gift_product['gift'] = true;
                        $gift_products[] = $gift_product;

                        break; // One gift product per cart rule
                    }
            }
        }
        // foreach ($cart_rules as $key => &$cart_rule)
        //  if ($cart_rule['value_real'] == 0)
        //      unset($cart_rules[$key]);

        return array(
            'delivery' => $delivery,
            'delivery_state' => State::getNameById($delivery->id_state),
            'invoice' => $invoice,
            'invoice_state' => State::getNameById($invoice->id_state),
            'formattedAddresses' => $formatted_addresses,
            'products' => array_values($products),
            'gift_products' => $gift_products,
            'discounts' => array_values($cart_rules),
            'is_virtual_cart' => (int)$this->isVirtualCart(),
            'total_discounts' => $total_discounts,
            'total_discounts_tax_exc' => $total_discounts_tax_exc,
            'total_wrapping' => $this->getOrderTotal(true, Cart::ONLY_WRAPPING),
            'total_wrapping_tax_exc' => $this->getOrderTotal(false, Cart::ONLY_WRAPPING),
            'total_shipping' => $total_shipping,
            'total_shipping_tax_exc' => $total_shipping_tax_exc,
            'total_products_wt' => $total_products_wt,
            'total_products' => $total_products,
            'total_price' => $base_total_tax_inc,
            'total_tax' => $total_tax,
            'total_price_without_tax' => $base_total_tax_exc,
            'is_multi_address_delivery' => $this->isMultiAddressDelivery() || ((int)Tools::getValue('multi-shipping') == 1),
            'free_ship' => $total_shipping ? 0 : 1,
            'carrier' => new Carrier($this->id_carrier, $id_lang),
        );
    }

    /**
     * Set only one cart rule with max discount sum.
     *
     */
    public function applyMaxCartRuleDiscount($allow_free_shipping = true)
    {
        $cart_rules = $this->getCartRules(CartRule::FILTER_ACTION_REDUCTION);

        $max_discount = 0;
        $max_cart_rule_id = 0;
        foreach ($cart_rules as $cart_rule) {
            if ($cart_rule['value_real'] > $max_discount) {
                $max_discount = $cart_rule['value_real'];
                $max_cart_rule_id = $cart_rule['id_cart_rule'];
            }
        }

        foreach ($cart_rules as $cart_rule) {
            if ($cart_rule['id_cart_rule'] != $max_cart_rule_id) {
                $this->removeCartRule($cart_rule['id_cart_rule']);
            }
        }

        // Apply shipping
        if ($allow_free_shipping && $cart_rule['free_shipping']) {
            $cart_rules = $this->getCartRules(CartRule::FILTER_ACTION_SHIPPING);

            $max_discount = 0;
            $max_cart_rule_id = 0;
            foreach ($cart_rules as $cart_rule) {
                if ($cart_rule['value_real'] > $max_discount) {
                    $max_discount = $cart_rule['value_real'];
                    $max_cart_rule_id = $cart_rule['id_cart_rule'];
                }
            }

            foreach ($cart_rules as $cart_rule) {
                if ($cart_rule['id_cart_rule'] != $max_cart_rule_id) {
                    $this->removeCartRule($cart_rule['id_cart_rule']);
                }
            }
        }
    }

    public function getTotalShippingCost($delivery_option = null, $use_tax = true, Country $default_country = null)
    {
        if (isset(Context::getContext()->cookie->id_country))
            $default_country = new Country(Context::getContext()->cookie->id_country);
        if (is_null($delivery_option))
            $delivery_option = $this->getDeliveryOption($default_country, false, false);

        $total_shipping = 0;
        $delivery_option_list = $this->getDeliveryOptionList($default_country, true);

        foreach ($delivery_option as $id_address => $key) {
            if (!isset($delivery_option_list[$id_address]) || !isset($delivery_option_list[$id_address][$key]))
                continue;
            if ($use_tax)
                $total_shipping += $delivery_option_list[$id_address][$key]['total_price_with_tax'];
            else
                $total_shipping += $delivery_option_list[$id_address][$key]['total_price_without_tax'];
        }

        return $total_shipping;
    }

    /**
     * @param mixed $calculated_delivery_cost
     */
    public function setCalculatedDeliveryCost($calculated_delivery_cost)
    {
        $this->calculated_delivery_cost = $calculated_delivery_cost;

        $this->update();
    }


    /**
     * Return cart weight
     * @return float Cart weight
     */
    public function getTotalWeight($products = null)
    {

        if (!is_null($products))
        {
            $total_weight = 0;
            foreach ($products as $product)
            {
                if($product['quantity_available']){
                    if (!isset($product['weight_attribute']) || is_null($product['weight_attribute']))
                        $total_weight += $product['weight'] * $product['cart_quantity'];
                    else
                        $total_weight += $product['weight_attribute'] * $product['cart_quantity'];
                }
            }
            return $total_weight;
        }

        if (!isset(self::$_totalWeight[$this->id]))
        {
            if (Combination::isFeatureActive())
                $weight_product_with_attribute = Db::getInstance()->getValue('
				SELECT SUM((p.`weight` + pa.`weight`) * cp.`quantity`) as nb
				FROM `'._DB_PREFIX_.'cart_product` cp
				LEFT JOIN `'._DB_PREFIX_.'product` p ON (cp.`id_product` = p.`id_product`)
				LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON (cp.`id_product_attribute` = pa.`id_product_attribute`)
				WHERE (cp.`id_product_attribute` IS NOT NULL AND cp.`id_product_attribute` != 0)
				AND cp.`id_cart` = '.(int)$this->id);
            else
                $weight_product_with_attribute = 0;

            $weight_product_without_attribute = Db::getInstance()->getValue('
			SELECT SUM(p.`weight` * cp.`quantity`) as nb
			FROM `'._DB_PREFIX_.'cart_product` cp
			LEFT JOIN `'._DB_PREFIX_.'product` p ON (cp.`id_product` = p.`id_product`)
			WHERE (cp.`id_product_attribute` IS NULL OR cp.`id_product_attribute` = 0)
			AND cp.`id_cart` = '.(int)$this->id);

            $products = $this->getProducts();
            $weight = round((float)$weight_product_with_attribute + (float)$weight_product_without_attribute, 3);
            foreach ($products as $product)
            {
                if($product['quantity_available'] == 0){
                    if (!isset($product['weight_attribute']) || is_null($product['weight_attribute']))
                        $weight -= $product['weight'] * $product['cart_quantity'];
                    else
                        $weight -= $product['weight_attribute'] * $product['cart_quantity'];
                }
            }
            self::$_totalWeight[$this->id] = $weight;
        }
        return self::$_totalWeight[$this->id];
    }

    public function getPackageShippingCost($id_carrier = null, $use_tax = true, Country $default_country = null, $product_list = null, $id_zone = null, $postcode = null)
    {
        if ($this->isVirtualCart())
            return 0;

        if (!$default_country)
            $default_country = Context::getContext()->country;

        $complete_product_list = $this->getProducts();
        if (is_null($product_list))
            $products = $complete_product_list;
        else
            $products = $product_list;

        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice')
            $address_id = (int)$this->id_address_invoice;
        elseif (count($product_list)) {
            $prod = current($product_list);
            $address_id = (int)$prod['id_address_delivery'];
        } else
            $address_id = null;
        if (!Address::addressExists($address_id))
            $address_id = null;

        $cache_id = 'getPackageShippingCost_' . (int)$this->id . '_' . (int)$address_id . '_' . (int)$id_carrier . '_' . (int)$use_tax . '_' . (int)$default_country->id . '_' . (int)((float)$this->getTotalWeight() * 1000);
        if ($products)
            foreach ($products as $product)
                $cache_id .= '_' . (int)$product['id_product'] . '_' . (int)$product['id_product_attribute'];

        if (Cache::isStored($cache_id))
            return Cache::retrieve($cache_id);

        // Order total in default currency without fees
        $order_total = $this->getOrderTotal(true, Cart::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING, $product_list);

        // Start with shipping cost at 0
        $shipping_cost = 0;
        // If no product added, return 0
        if (!count($products)) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }

        if (!isset($id_zone)) {
            // Get id zone
            if (!$this->isMultiAddressDelivery()
                && isset($this->id_address_delivery) // Be carefull, id_address_delivery is not usefull one 1.5
                && $this->id_address_delivery
                && Customer::customerHasAddress($this->id_customer, $this->id_address_delivery
                )
            )
                $id_zone = Address::getZoneById((int)$this->id_address_delivery);
            else {
                if (!Validate::isLoadedObject($default_country))
                    $default_country = new Country(Configuration::get('PS_COUNTRY_DEFAULT'), Configuration::get('PS_LANG_DEFAULT'));

                $id_zone = (int)$default_country->id_zone;
            }
        }

        if ($id_carrier && !$this->isCarrierInRange((int)$id_carrier, (int)$id_zone))
            $id_carrier = '';

        if (empty($id_carrier) && $this->isCarrierInRange((int)Configuration::get('PS_CARRIER_DEFAULT'), (int)$id_zone))
            $id_carrier = (int)Configuration::get('PS_CARRIER_DEFAULT');

        $total_package_without_shipping_tax_inc = $this->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING, $product_list);
        if (empty($id_carrier)) {
            if ((int)$this->id_customer) {
                $customer = new Customer((int)$this->id_customer);
                $result = Carrier::getCarriers((int)Configuration::get('PS_LANG_DEFAULT'), true, false, (int)$id_zone, $customer->getGroups());
                unset($customer);
            } else
                $result = Carrier::getCarriers((int)Configuration::get('PS_LANG_DEFAULT'), true, false, (int)$id_zone);

            foreach ($result as $k => $row) {
                if ($row['id_carrier'] == Configuration::get('PS_CARRIER_DEFAULT'))
                    continue;

                if (!isset(self::$_carriers[$row['id_carrier']]))
                    self::$_carriers[$row['id_carrier']] = new Carrier((int)$row['id_carrier']);

                $carrier = self::$_carriers[$row['id_carrier']];

                // Get only carriers that are compliant with shipping method
                if (($carrier->getShippingMethod() == Carrier::SHIPPING_METHOD_WEIGHT && $carrier->getMaxDeliveryPriceByWeight((int)$id_zone) === false)
                    || ($carrier->getShippingMethod() == Carrier::SHIPPING_METHOD_PRICE && $carrier->getMaxDeliveryPriceByPrice((int)$id_zone) === false)
                ) {
                    unset($result[$k]);
                    continue;
                }

                // If out-of-range behavior carrier is set on "Desactivate carrier"
                if ($row['range_behavior']) {
                    $check_delivery_price_by_weight = Carrier::checkDeliveryPriceByWeight($row['id_carrier'], $this->getTotalWeight(), (int)$id_zone);

                    $total_order = $total_package_without_shipping_tax_inc;
                    $check_delivery_price_by_price = Carrier::checkDeliveryPriceByPrice($row['id_carrier'], $total_order, (int)$id_zone, (int)$this->id_currency);

                    // Get only carriers that have a range compatible with cart
                    if (($carrier->getShippingMethod() == Carrier::SHIPPING_METHOD_WEIGHT && !$check_delivery_price_by_weight)
                        || ($carrier->getShippingMethod() == Carrier::SHIPPING_METHOD_PRICE && !$check_delivery_price_by_price)
                    ) {
                        unset($result[$k]);
                        continue;
                    }
                }

                if ($carrier->getShippingMethod() == Carrier::SHIPPING_METHOD_WEIGHT)
                    $shipping = $carrier->getDeliveryPriceByWeight($this->getTotalWeight($product_list), (int)$id_zone);
                else
                    $shipping = $carrier->getDeliveryPriceByPrice($order_total, (int)$id_zone, (int)$this->id_currency);

                if (!isset($min_shipping_price))
                    $min_shipping_price = $shipping;

                if ($shipping <= $min_shipping_price) {
                    $id_carrier = (int)$row['id_carrier'];
                    $min_shipping_price = $shipping;
                }
            }
        }

        if (empty($id_carrier))
            $id_carrier = Configuration::get('PS_CARRIER_DEFAULT');

        if (!isset(self::$_carriers[$id_carrier]))
            self::$_carriers[$id_carrier] = new Carrier((int)$id_carrier, Configuration::get('PS_LANG_DEFAULT'));

        $carrier = self::$_carriers[$id_carrier];

        // No valid Carrier or $id_carrier <= 0 ?
        if (!Validate::isLoadedObject($carrier)) {
            Cache::store($cache_id, 0);
            return 0;
        }

        if (!$carrier->active) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }

        // Free fees if free carrier
        if ($carrier->is_free == 1) {
            Cache::store($cache_id, 0);
            return 0;
        }

        // Select carrier tax
        if ($use_tax && !Tax::excludeTaxeOption()) {
            $address = Address::initialize((int)$address_id);
            $carrier_tax = $carrier->getTaxesRate($address);
        }

        $configuration = Configuration::getMultiple(array(
            'PS_SHIPPING_FREE_PRICE',
            'PS_SHIPPING_HANDLING',
            'PS_SHIPPING_METHOD',
            'PS_SHIPPING_FREE_WEIGHT'
        ));

        // Free fees
        $free_fees_price = 0;
        if (isset($configuration['PS_SHIPPING_FREE_PRICE']))
            $free_fees_price = Tools::convertPrice((float)$configuration['PS_SHIPPING_FREE_PRICE'], Currency::getCurrencyInstance((int)$this->id_currency));
        $orderTotalwithDiscounts = $this->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING, null, null, false);
        if ($orderTotalwithDiscounts >= (float)($free_fees_price) && (float)($free_fees_price) > 0) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }

        if (isset($configuration['PS_SHIPPING_FREE_WEIGHT'])
            && $this->getTotalWeight() >= (float)$configuration['PS_SHIPPING_FREE_WEIGHT']
            && (float)$configuration['PS_SHIPPING_FREE_WEIGHT'] > 0
        ) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }

        // Get shipping cost using correct method
        if ($carrier->range_behavior) {
            if (!isset($id_zone)) {
                // Get id zone
                if (isset($this->id_address_delivery)
                    && $this->id_address_delivery
                    && Customer::customerHasAddress($this->id_customer, $this->id_address_delivery)
                )
                    $id_zone = Address::getZoneById((int)$this->id_address_delivery);
                else
                    $id_zone = (int)$default_country->id_zone;
            }

            if (($carrier->getShippingMethod() == Carrier::SHIPPING_METHOD_WEIGHT && !Carrier::checkDeliveryPriceByWeight($carrier->id, $this->getTotalWeight(), (int)$id_zone))
                || ($carrier->getShippingMethod() == Carrier::SHIPPING_METHOD_PRICE && !Carrier::checkDeliveryPriceByPrice($carrier->id, $total_package_without_shipping_tax_inc, $id_zone, (int)$this->id_currency)
                )
            )
                $shipping_cost += 0;
            else {
                if ($carrier->getShippingMethod() == Carrier::SHIPPING_METHOD_WEIGHT)
                    $shipping_cost += $carrier->getDeliveryPriceByWeight($this->getTotalWeight($product_list), $id_zone);
                else // by price
                    $shipping_cost += $carrier->getDeliveryPriceByPrice($order_total, $id_zone, (int)$this->id_currency);
            }
        } else {
            if ($carrier->getShippingMethod() == Carrier::SHIPPING_METHOD_WEIGHT)
                $shipping_cost += $carrier->getDeliveryPriceByWeight($this->getTotalWeight($product_list), $id_zone);
            else
                $shipping_cost += $carrier->getDeliveryPriceByPrice($order_total, $id_zone, (int)$this->id_currency);

        }
        // Adding handling charges
        if (isset($configuration['PS_SHIPPING_HANDLING']) && $carrier->shipping_handling)
            $shipping_cost += (float)$configuration['PS_SHIPPING_HANDLING'];

        // Additional Shipping Cost per product
        foreach ($products as $product)
            if (!$product['is_virtual'])
                $shipping_cost += $product['additional_shipping_cost'] * $product['cart_quantity'];

        $shipping_cost = Tools::convertPrice($shipping_cost, Currency::getCurrencyInstance((int)$this->id_currency));

        //get external shipping cost from module
        if ($carrier->shipping_external) {
            $module_name = $carrier->external_module_name;
            $module = Module::getInstanceByName($module_name);

            if (Validate::isLoadedObject($module)) {
                if (array_key_exists('id_carrier', $module))
                    $module->id_carrier = $carrier->id;
                if ($carrier->need_range)
                    if (method_exists($module, 'getPackageShippingCost'))
                        $shipping_cost = $module->getPackageShippingCost($this, $shipping_cost, $products);
                    else
                        $shipping_cost = $module->getOrderShippingCost($this, $shipping_cost);
                else
                    $shipping_cost = $module->getOrderShippingCostExternal($this);

                // Check if carrier is available
                if ($shipping_cost === false) {
                    Cache::store($cache_id, false);
                    return false;
                }
            } else {
                Cache::store($cache_id, false);
                return false;
            }
        }

        // Apply tax
        if ($use_tax && isset($carrier_tax))
            $shipping_cost *= 1 + ($carrier_tax / 100);


        // If Russian Post then calculate delivery via API
        if ($id_carrier == 22) {
            // Todo: get postal code
            if (!$postcode && $address_id) {
                $address = Address::initialize((int)$address_id);

                $postcode = $address->postcode;
            }

            $shipping_cost = $this->getRussianPostDelivery($postcode, $orderTotalwithDiscounts);
        }


        $shipping_cost = (float)Tools::ps_round((float)$shipping_cost, 2);
        Cache::store($cache_id, $shipping_cost);

        return $shipping_cost;
    }

    public function getCalculatedDeliveryCost()
    {
//        $delivery_option = $this->getDeliveryOption()[0];
//
//        $delivery_cost = 0;
//
//        // Russian post
//        if ($delivery_option == '22,') {
//            // Todo: move to separate method to avoid duplications
//            $calculation_helper = new HelperRussianPostDelivery();
//            $postcode = null;
//
//            if ($this->id_address_delivery != 0) {
//                $address = new Address($this->id_address_delivery);
//                $postcode = $address->postcode;
//            }
//
////            if ( ! $postcode) {
////                echo ' *1* ';
////                return 0;
////            }
////
////            echo ' *2* ';
////            $response = $calculation_helper->russianpostcalc_api_calc($postcode, $this->getTotalWeight(), $this->getOrderTotal(false));
////
////            if ( ! isset($response['error']) && isset($response['delivery_cost'])) {
////                $this->setCalculatedDeliveryCost($response['delivery_cost']);
////                $delivery_cost = $response['delivery_cost'];
////            }
//        }
//
//        return (float)$delivery_cost;
    }

    /**
     * This function returns the total cart amount
     *
     * Possible values for $type:
     * Cart::ONLY_PRODUCTS
     * Cart::ONLY_DISCOUNTS
     * Cart::BOTH
     * Cart::BOTH_WITHOUT_SHIPPING
     * Cart::ONLY_SHIPPING
     * Cart::ONLY_WRAPPING
     * Cart::ONLY_PRODUCTS_WITHOUT_SHIPPING
     * Cart::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING
     *
     * @param boolean $withTaxes With or without taxes
     * @param integer $type Total type
     * @param boolean $use_cache Allow using cache of the method CartRule::getContextualValue
     * @return float Order total
     */
    public function getOrderTotal($with_taxes = true, $type = Cart::BOTH, $products = null, $id_carrier = null, $use_cache = true)
    {
        static $address = null;

        if (!$this->id)
            return 0;

        $type = (int)$type;
        $array_type = array(
            Cart::ONLY_PRODUCTS,
            Cart::ONLY_DISCOUNTS,
            Cart::BOTH,
            Cart::BOTH_WITHOUT_SHIPPING,
            Cart::ONLY_SHIPPING,
            Cart::ONLY_WRAPPING,
            Cart::ONLY_PRODUCTS_WITHOUT_SHIPPING,
            Cart::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING,
        );

        // Define virtual context to prevent case where the cart is not the in the global context
        $virtual_context = Context::getContext()->cloneContext();
        $virtual_context->cart = $this;

        if (!in_array($type, $array_type))
            die(Tools::displayError());

        $with_shipping = in_array($type, array(Cart::BOTH, Cart::ONLY_SHIPPING));

        // if cart rules are not used
        if ($type == Cart::ONLY_DISCOUNTS && !CartRule::isFeatureActive())
            return 0;

        // no shipping cost if is a cart with only virtuals products
        $virtual = $this->isVirtualCart();
        if ($virtual && $type == Cart::ONLY_SHIPPING)
            return 0;

        if ($virtual && $type == Cart::BOTH)
            $type = Cart::BOTH_WITHOUT_SHIPPING;

        $shipping_fees = 0;
        // if ($with_shipping || $type == Cart::ONLY_DISCOUNTS)
        if ($with_shipping) {
            if (is_null($products) && is_null($id_carrier))
                $shipping_fees = $this->getTotalShippingCost(null, (boolean)$with_taxes);
            else
                $shipping_fees = $this->getPackageShippingCost($id_carrier, (bool)$with_taxes, null, $products);
        }

        if ($type == Cart::ONLY_SHIPPING)
            return $shipping_fees;

        if ($type == Cart::ONLY_PRODUCTS_WITHOUT_SHIPPING)
            $type = Cart::ONLY_PRODUCTS;

        $param_product = true;
        if (is_null($products)) {
            $param_product = false;
            $products = $this->getProducts();
        }

        if ($type == Cart::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING) {
            foreach ($products as $key => $product)
                if ($product['is_virtual'])
                    unset($products[$key]);
            $type = Cart::ONLY_PRODUCTS;
        }

        $order_total = 0;
        if (Tax::excludeTaxeOption())
            $with_taxes = false;

        $products_total = array();
        $ecotax_total = 0;

        foreach ($products as $product) // products refer to the cart details
        {

            if ($virtual_context->shop->id != $product['id_shop'])
                $virtual_context->shop = new Shop((int)$product['id_shop']);

            if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice')
                $id_address = (int)$this->id_address_invoice;
            else
                $id_address = (int)$product['id_address_delivery']; // Get delivery address of the product from the cart
            if (!Address::addressExists($id_address))
                $id_address = null;

            $price = Product::getPriceStatic(
                (int)$product['id_product'],
                false,
                (int)$product['id_product_attribute'],
                6,
                null,
                false,
                true,
                $product['cart_quantity'],
                false,
                (int)$this->id_customer ? (int)$this->id_customer : null,
                (int)$this->id,
                $id_address,
                $null,
                false,
                true,
                $virtual_context
            );
            if ($product['quantity_available'] == 0)
                $price = 0;
            if (Configuration::get('PS_USE_ECOTAX')) {
                $ecotax = $product['ecotax'];
                if (isset($product['attribute_ecotax']) && $product['attribute_ecotax'] > 0)
                    $ecotax = $product['attribute_ecotax'];
            } else
                $ecotax = 0;

            $address = Address::initialize($id_address, true);

            if ($with_taxes) {
                $id_tax_rules_group = Product::getIdTaxRulesGroupByIdProduct((int)$product['id_product'], $virtual_context);
                $tax_calculator = TaxManagerFactory::getManager($address, $id_tax_rules_group)->getTaxCalculator();

                if ($ecotax)
                    $ecotax_tax_calculator = TaxManagerFactory::getManager($address, (int)Configuration::get('PS_ECOTAX_TAX_RULES_GROUP_ID'))->getTaxCalculator();
            } else
                $id_tax_rules_group = 0;

            if (in_array(Configuration::get('PS_ROUND_TYPE'), array(Order::ROUND_ITEM, Order::ROUND_LINE))) {
                if (!isset($products_total[$id_tax_rules_group]))
                    $products_total[$id_tax_rules_group] = 0;
            } else
                if (!isset($products_total[$id_tax_rules_group . '_' . $id_address]))
                    $products_total[$id_tax_rules_group . '_' . $id_address] = 0;

            switch (Configuration::get('PS_ROUND_TYPE')) {
                case Order::ROUND_TOTAL:
                    $products_total[$id_tax_rules_group . '_' . $id_address] += $price * (int)$product['cart_quantity'];

                    // if ($ecotax)
                    //  $ecotax_total += $ecotax * (int)$product['cart_quantity'];
                    break;
                case Order::ROUND_LINE:
                    $product_price = $price * $product['cart_quantity'];
                    $products_total[$id_tax_rules_group] += Tools::ps_round($product_price, _PS_PRICE_COMPUTE_PRECISION_);

                    if ($with_taxes)
                        $products_total[$id_tax_rules_group] += Tools::ps_round($tax_calculator->getTaxesTotalAmount($product_price), _PS_PRICE_COMPUTE_PRECISION_);

                    // if ($ecotax)
                    // {
                    //  $ecotax_price = $ecotax * (int)$product['cart_quantity'];
                    //  $ecotax_total += Tools::ps_round($ecotax_price, _PS_PRICE_COMPUTE_PRECISION_);

                    //  if ($with_taxes)
                    //      $ecotax_total += Tools::ps_round($ecotax_tax_calculator->getTaxesTotalAmount($ecotax_price), _PS_PRICE_COMPUTE_PRECISION_);
                    // }
                    break;
                case Order::ROUND_ITEM:
                default:
                    $product_price = $with_taxes ? $tax_calculator->addTaxes($price) : $price;
                    $products_total[$id_tax_rules_group] += Tools::ps_round($product_price, _PS_PRICE_COMPUTE_PRECISION_) * (int)$product['cart_quantity'];

                    // if ($ecotax)
                    // {
                    //  $ecotax_price = $with_taxes ? $ecotax_tax_calculator->addTaxes($ecotax) : $ecotax;
                    //  $ecotax_total += Tools::ps_round($ecotax_price, _PS_PRICE_COMPUTE_PRECISION_) * (int)$product['cart_quantity'];
                    // }
                    break;
            }
        }
        foreach ($products_total as $key => $price) {
            if ($with_taxes && Configuration::get('PS_ROUND_TYPE') == Order::ROUND_TOTAL) {
                $tmp = explode('_', $key);
                $address = Address::initialize((int)$tmp[1], true);
                $tax_calculator = TaxManagerFactory::getManager($address, $tmp[0])->getTaxCalculator();
                $order_total += Tools::ps_round($price, _PS_PRICE_COMPUTE_PRECISION_) + Tools::ps_round($tax_calculator->getTaxesTotalAmount($price), _PS_PRICE_COMPUTE_PRECISION_);
            } else
                $order_total += $price;


        }

        // if ($ecotax_total && $with_taxes && Configuration::get('PS_ROUND_TYPE') == Order::ROUND_TOTAL)
        //  $ecotax_total = Tools::ps_round($ecotax_total, _PS_PRICE_COMPUTE_PRECISION_) + Tools::ps_round($ecotax_tax_calculator->getTaxesTotalAmount($ecotax_total), _PS_PRICE_COMPUTE_PRECISION_);

        // $order_total += $ecotax_total;
        $order_total_products = $order_total;

        if ($type == Cart::ONLY_DISCOUNTS)
            $order_total = 0;

        // Wrapping Fees
        $wrapping_fees = 0;
        // if ($this->gift)
        //  $wrapping_fees = Tools::convertPrice(Tools::ps_round($this->getGiftWrappingPrice($with_taxes), _PS_PRICE_COMPUTE_PRECISION_), Currency::getCurrencyInstance((int)$this->id_currency));
        if ($type == Cart::ONLY_WRAPPING)
            return $wrapping_fees;

        $order_total_discount = 0;
        if (!in_array($type, array(Cart::ONLY_SHIPPING, Cart::ONLY_PRODUCTS)) && CartRule::isFeatureActive()) {
            // First, retrieve the cart rules associated to this "getOrderTotal"
            if ($with_shipping || $type == Cart::ONLY_DISCOUNTS)
                // $cart_rules = $this->getCartRules(CartRule::FILTER_ACTION_ALL);
                $cart_rules = $this->getCartRules(CartRule::FILTER_ACTION_REDUCTION);
            else {
                $cart_rules = $this->getCartRules(CartRule::FILTER_ACTION_REDUCTION);
                // Cart Rules array are merged manually in order to avoid doubles
                foreach ($this->getCartRules(CartRule::FILTER_ACTION_GIFT) as $tmp_cart_rule) {
                    $flag = false;
                    foreach ($cart_rules as $cart_rule)
                        if ($tmp_cart_rule['id_cart_rule'] == $cart_rule['id_cart_rule'])
                            $flag = true;
                    if (!$flag)
                        $cart_rules[] = $tmp_cart_rule;
                }
            }

            $id_address_delivery = 0;
            if (isset($products[0]))
                $id_address_delivery = (is_null($products) ? $this->id_address_delivery : $products[0]['id_address_delivery']);
            $package = array('id_carrier' => $id_carrier, 'id_address' => $id_address_delivery, 'products' => $products);

            // Then, calculate the contextual value for each one
            foreach ($cart_rules as $cart_rule) {
                // If the cart rule offers free shipping, add the shipping cost
                // if (($with_shipping || $type == Cart::ONLY_DISCOUNTS) && $cart_rule['obj']->free_shipping)
                if (($with_shipping) && $cart_rule['obj']->free_shipping)
                    $order_total_discount += Tools::ps_round($cart_rule['obj']->getContextualValue($with_taxes, $virtual_context, CartRule::FILTER_ACTION_SHIPPING, ($param_product ? $package : null), $use_cache), _PS_PRICE_COMPUTE_PRECISION_);

                // If the cart rule is a free gift, then add the free gift value only if the gift is in this package
                if ((int)$cart_rule['obj']->gift_product) {
                    $in_order = false;
                    if (is_null($products))
                        $in_order = true;
                    else
                        foreach ($products as $product)
                            if ($cart_rule['obj']->gift_product == $product['id_product'] && $cart_rule['obj']->gift_product_attribute == $product['id_product_attribute'])
                                $in_order = true;

                    if ($in_order)
                        $order_total_discount += $cart_rule['obj']->getContextualValue($with_taxes, $virtual_context, CartRule::FILTER_ACTION_GIFT, $package, $use_cache);
                }

                // If the cart rule offers a reduction, the amount is prorated (with the products in the package)
                if ($cart_rule['obj']->reduction_percent > 0 || $cart_rule['obj']->reduction_amount > 0)
                    $order_total_discount += Tools::ps_round($cart_rule['obj']->getContextualValue($with_taxes, $virtual_context, CartRule::FILTER_ACTION_REDUCTION, $package, $use_cache), _PS_PRICE_COMPUTE_PRECISION_);
            }
            $order_total_discount = min(Tools::ps_round($order_total_discount, 2), $wrapping_fees + $order_total_products + $shipping_fees);
            $order_total -= $order_total_discount;
        }

        if ($type == Cart::BOTH)
            $order_total += $shipping_fees + $wrapping_fees;

        if ($order_total < 0 && $type != Cart::ONLY_DISCOUNTS)
            return 0;

        if ($type == Cart::ONLY_DISCOUNTS)
            return $order_total_discount;

        return Tools::ps_round((float)$order_total, _PS_PRICE_COMPUTE_PRECISION_);
    }

    /**
     * @param $postcode
     * @param $order_total_price
     * @return mixed
     * @internal param $shipping_cost
     */
    protected function getRussianPostDelivery($postcode, $order_total_price)
    {
        if (!$postcode) {
            return 0;
        }

        $total_weight = $this->getTotalWeight();

        $calculation_helper = new HelperRussianPostDelivery();
        $response = $calculation_helper->get_calculation($postcode, $total_weight, $order_total_price);

        if (!isset($response['error']) && isset($response['delivery_cost'])) {
            // Success
//                $this->context->cart->setCalculatedDeliveryCost($response['delivery_cost']);
            return $response['delivery_cost'];
        }

        return 0;
    }

    public function getDeliveryOption($default_country = null, $dontAutoSelectOptions = false, $use_cache = true)
    {
        static $cache = array();
        $cache_id = (int)(is_object($default_country) ? $default_country->id : 0).'-'.(int)$dontAutoSelectOptions;
        if (isset($cache[$cache_id]) && $use_cache)
            return $cache[$cache_id];

        $delivery_option_list = $this->getDeliveryOptionList($default_country);

        // The delivery option was selected
        if (isset($this->delivery_option) && $this->delivery_option != '')
        {
            $delivery_option = Tools::unSerialize($this->delivery_option);

            $validated = true;

            foreach ($delivery_option as $id_address => $key)
                if (!isset($delivery_option_list[$id_address][$key]))
                {
                    $validated = false;
                    break;
                }
            if($this->id_carrier == "27"){
                $key = array_keys($delivery_option_list);
                $value = array_values($delivery_option);
                $delivery_option = array($key[0] => $value[0]);
                $validated = true;
            }
            if ($validated)
            {
                $cache[$cache_id] = $delivery_option;
                return $delivery_option;
            }
        }

        if ($dontAutoSelectOptions)
            return false;

        // No delivery option selected or delivery option selected is not valid, get the better for all options
        $delivery_option = array();
        foreach ($delivery_option_list as $id_address => $options)
        {
            foreach ($options as $key => $option)
                if (Configuration::get('PS_CARRIER_DEFAULT') == -1 && $option['is_best_price'])
                {
                    $delivery_option[$id_address] = $key;
                    break;
                }
                elseif (Configuration::get('PS_CARRIER_DEFAULT') == -2 && $option['is_best_grade'])
                {
                    $delivery_option[$id_address] = $key;
                    break;
                }
                elseif ($option['unique_carrier'] && in_array(Configuration::get('PS_CARRIER_DEFAULT'), array_keys($option['carrier_list'])))
                {
                    $delivery_option[$id_address] = $key;
                    break;
                }

            reset($options);
            if (!isset($delivery_option[$id_address]))
                $delivery_option[$id_address] = key($options);
        }

        $cache[$cache_id] = $delivery_option;

        return $delivery_option;
    }

    public function getDeliveryOptionList(Country $default_country = null, $flush = false)
    {
        static $cache = array();
        // if (isset($cache[$this->id]) && !$flush)
        //  return $cache[$this->id];

        $delivery_option_list = array();
        $carriers_price = array();
        $carrier_collection = array();
        $package_list = $this->getPackageList();

        // Foreach addresses
        foreach ($package_list as $id_address => $packages) {
            // Initialize vars
            $delivery_option_list[$id_address] = array();
            $carriers_price[$id_address] = array();
            $common_carriers = null;
            $best_price_carriers = array();
            $best_grade_carriers = array();
            $carriers_instance = array();

            // Get country
            if ($id_address) {
                $address = new Address($id_address);
                $country = new Country($address->id_country);
            } else
                $country = $default_country;

            // Foreach packages, get the carriers with best price, best position and best grade
            foreach ($packages as $id_package => $package) {
                // No carriers available
                if (count($packages) == 1 && count($package['carrier_list']) == 1 && current($package['carrier_list']) == 0) {
                    $cache[$this->id] = array();
                    return $cache[$this->id];
                }

                $carriers_price[$id_address][$id_package] = array();

                // Get all common carriers for each packages to the same address
                if (is_null($common_carriers))
                    $common_carriers = $package['carrier_list'];
                else
                    $common_carriers = array_intersect($common_carriers, $package['carrier_list']);

                $best_price = null;
                $best_price_carrier = null;
                $best_grade = null;
                $best_grade_carrier = null;

                // Foreach carriers of the package, calculate his price, check if it the best price, position and grade
                foreach ($package['carrier_list'] as $id_carrier) {
                    if (!isset($carriers_instance[$id_carrier]))
                        $carriers_instance[$id_carrier] = new Carrier($id_carrier);

                    $price_with_tax = $this->getPackageShippingCost($id_carrier, true, $country, $package['product_list']);
                    $price_without_tax = $this->getPackageShippingCost($id_carrier, false, $country, $package['product_list']);
                    if (is_null($best_price) || $price_with_tax < $best_price) {
                        $best_price = $price_with_tax;
                        $best_price_carrier = $id_carrier;
                    }
                    $carriers_price[$id_address][$id_package][$id_carrier] = array(
                        'without_tax' => $price_without_tax,
                        'with_tax' => $price_with_tax);

                    $grade = $carriers_instance[$id_carrier]->grade;
                    if (is_null($best_grade) || $grade > $best_grade) {
                        $best_grade = $grade;
                        $best_grade_carrier = $id_carrier;
                    }
                }

                $best_price_carriers[$id_package] = $best_price_carrier;
                $best_grade_carriers[$id_package] = $best_grade_carrier;
            }

            // Reset $best_price_carrier, it's now an array
            $best_price_carrier = array();
            $key = '';

            // Get the delivery option with the lower price
            foreach ($best_price_carriers as $id_package => $id_carrier) {
                $key .= $id_carrier . ',';
                if (!isset($best_price_carrier[$id_carrier]))
                    $best_price_carrier[$id_carrier] = array(
                        'price_with_tax' => 0,
                        'price_without_tax' => 0,
                        'package_list' => array(),
                        'product_list' => array(),
                    );
                $best_price_carrier[$id_carrier]['price_with_tax'] += $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'];
                $best_price_carrier[$id_carrier]['price_without_tax'] += $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'];
                $best_price_carrier[$id_carrier]['package_list'][] = $id_package;
                $best_price_carrier[$id_carrier]['product_list'] = array_merge($best_price_carrier[$id_carrier]['product_list'], $packages[$id_package]['product_list']);
                $best_price_carrier[$id_carrier]['instance'] = $carriers_instance[$id_carrier];
                $real_best_price = !isset($real_best_price) || $real_best_price > $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'] ?
                    $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'] : $real_best_price;
                $real_best_price_wt = !isset($real_best_price_wt) || $real_best_price_wt > $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'] ?
                    $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'] : $real_best_price_wt;
            }

            // Add the delivery option with best price as best price
            $delivery_option_list[$id_address][$key] = array(
                'carrier_list' => $best_price_carrier,
                'is_best_price' => true,
                'is_best_grade' => false,
                'unique_carrier' => (count($best_price_carrier) <= 1)
            );

            // Reset $best_grade_carrier, it's now an array
            $best_grade_carrier = array();
            $key = '';

            // Get the delivery option with the best grade
            foreach ($best_grade_carriers as $id_package => $id_carrier) {
                $key .= $id_carrier . ',';
                if (!isset($best_grade_carrier[$id_carrier]))
                    $best_grade_carrier[$id_carrier] = array(
                        'price_with_tax' => 0,
                        'price_without_tax' => 0,
                        'package_list' => array(),
                        'product_list' => array(),
                    );
                $best_grade_carrier[$id_carrier]['price_with_tax'] += $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'];
                $best_grade_carrier[$id_carrier]['price_without_tax'] += $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'];
                $best_grade_carrier[$id_carrier]['package_list'][] = $id_package;
                $best_grade_carrier[$id_carrier]['product_list'] = array_merge($best_grade_carrier[$id_carrier]['product_list'], $packages[$id_package]['product_list']);
                $best_grade_carrier[$id_carrier]['instance'] = $carriers_instance[$id_carrier];
            }

            // Add the delivery option with best grade as best grade
            if (!isset($delivery_option_list[$id_address][$key]))
                $delivery_option_list[$id_address][$key] = array(
                    'carrier_list' => $best_grade_carrier,
                    'is_best_price' => false,
                    'unique_carrier' => (count($best_grade_carrier) <= 1)
                );
            $delivery_option_list[$id_address][$key]['is_best_grade'] = true;

            // Get all delivery options with a unique carrier
            foreach ($common_carriers as $id_carrier) {
                $key = '';
                $package_list = array();
                $product_list = array();
                $price_with_tax = 0;
                $price_without_tax = 0;

                foreach ($packages as $id_package => $package) {
                    $key .= $id_carrier . ',';
                    $price_with_tax += $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'];
                    $price_without_tax += $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'];
                    $package_list[] = $id_package;
                    $product_list = array_merge($product_list, $package['product_list']);
                }

                if (!isset($delivery_option_list[$id_address][$key]))
                    $delivery_option_list[$id_address][$key] = array(
                        'is_best_price' => false,
                        'is_best_grade' => false,
                        'unique_carrier' => true,
                        'carrier_list' => array(
                            $id_carrier => array(
                                'price_with_tax' => $price_with_tax,
                                'price_without_tax' => $price_without_tax,
                                'instance' => $carriers_instance[$id_carrier],
                                'package_list' => $package_list,
                                'product_list' => $product_list,
                            )
                        )
                    );
                else
                    $delivery_option_list[$id_address][$key]['unique_carrier'] = (count($delivery_option_list[$id_address][$key]['carrier_list']) <= 1);
            }
        }

        $cart_rules = CartRule::getCustomerCartRules(Context::getContext()->cookie->id_lang, Context::getContext()->cookie->id_customer, true, true, false, $this);
        $result = Db::getInstance('SELECT * FROM ' . _DB_PREFIX_ . 'cart_cart_rule WHERE id_cart=' . $this->id);
        $cart_rules_in_cart = array();

        foreach ($result as $row)
            $cart_rules_in_cart[] = $row['id_cart_rules'];

        $total_products_wt = $this->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $total_products = $this->getOrderTotal(false, Cart::ONLY_PRODUCTS);

        $free_carriers_rules = array();

        foreach ($cart_rules as $cart_rule) {
            $total_price = $cart_rule['minimum_amount_tax'] ? $total_products_wt : $total_products;
            $total_price += $cart_rule['minimum_amount_tax'] && $cart_rule['minimum_amount_shipping'] ? $real_best_price : 0;
            $total_price += !$cart_rule['minimum_amount_tax'] && $cart_rule['minimum_amount_shipping'] ? $real_best_price_wt : 0;
            if ($cart_rule['free_shipping'] && $cart_rule['carrier_restriction'] && $cart_rule['minimum_amount'] <= $total_price) {
                $cr = new CartRule((int)$cart_rule['id_cart_rule']);
                if (Validate::isLoadedObject($cr) &&
                    $cr->checkValidity(Context::getContext(), in_array((int)$cart_rule['id_cart_rule'], $cart_rules_in_cart), false, false)
                ) {
                    $carriers = $cr->getAssociatedRestrictions('carrier', true, false);
                    if (is_array($carriers) && count($carriers) && isset($carriers['selected']))
                        foreach ($carriers['selected'] as $carrier)
                            if (isset($carrier['id_carrier']) && $carrier['id_carrier'])
                                $free_carriers_rules[] = (int)$carrier['id_carrier'];
                }
            }
        }

        // For each delivery options :
        //    - Set the carrier list
        //    - Calculate the price
        //    - Calculate the average position
        foreach ($delivery_option_list as $id_address => $delivery_option)
            foreach ($delivery_option as $key => $value) {
                $total_price_with_tax = 0;
                $total_price_without_tax = 0;
                $position = 0;
                foreach ($value['carrier_list'] as $id_carrier => $data) {
                    $total_price_with_tax += $data['price_with_tax'];
                    $total_price_without_tax += $data['price_without_tax'];
                    $total_price_without_tax_with_rules = (in_array($id_carrier, $free_carriers_rules)) ? 0 : $total_price_without_tax;

                    if (!isset($carrier_collection[$id_carrier]))
                        $carrier_collection[$id_carrier] = new Carrier($id_carrier);
                    $delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier]['instance'] = $carrier_collection[$id_carrier];

                    if (file_exists(_PS_SHIP_IMG_DIR_ . $id_carrier . '.jpg'))
                        $delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier]['logo'] = _THEME_SHIP_DIR_ . $id_carrier . '.jpg';
                    else
                        $delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier]['logo'] = false;

                    $position += $carrier_collection[$id_carrier]->position;
                }
                $delivery_option_list[$id_address][$key]['total_price_with_tax'] = $total_price_with_tax;
                $delivery_option_list[$id_address][$key]['total_price_without_tax'] = $total_price_without_tax;
                $delivery_option_list[$id_address][$key]['is_free'] = !$total_price_without_tax_with_rules ? true : false;
                $delivery_option_list[$id_address][$key]['position'] = $position / count($value['carrier_list']);
            }

        // Sort delivery option list
        foreach ($delivery_option_list as &$array)
            uasort($array, array('Cart', 'sortDeliveryOptionList'));

        $cache[$this->id] = $delivery_option_list;
        return $delivery_option_list;
    }
}
