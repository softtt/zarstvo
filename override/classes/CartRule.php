<?php

class CartRule extends CartRuleCore
{
    /** @var boolean Check if cart rule applies to discount cards */
    public $is_discount_cart_rule;

    /** @var boolean Accumulated sum needed to apply this cart rule */
    public $accumulated_minimum_amount;


    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'cart_rule',
        'primary' => 'id_cart_rule',
        'multilang' => true,
        'fields' => array(
            'id_customer' =>            array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'date_from' =>              array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true),
            'date_to' =>                array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true),
            'description' =>            array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 65534),
            'quantity' =>               array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'quantity_per_user' =>      array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'priority' =>               array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'partial_use' =>            array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'code' =>                   array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 254),
            'minimum_amount' =>         array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'minimum_amount_tax' =>     array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'minimum_amount_currency' =>array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'minimum_amount_shipping' =>array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'country_restriction' =>    array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'carrier_restriction' =>    array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'group_restriction' =>      array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'cart_rule_restriction' =>  array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'product_restriction' =>    array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'shop_restriction' =>       array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'free_shipping' =>          array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'reduction_percent' =>      array('type' => self::TYPE_FLOAT, 'validate' => 'isPercentage'),
            'reduction_amount' =>       array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'reduction_tax' =>          array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'reduction_currency' =>     array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'reduction_product' =>      array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'gift_product' =>           array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'gift_product_attribute' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'highlight' =>              array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'active' =>                 array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'date_add' =>               array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' =>               array('type' => self::TYPE_DATE, 'validate' => 'isDate'),

            // Lang fields
            'name' =>                   array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 254),

            'is_discount_cart_rule' =>  array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'accumulated_minimum_amount' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
        ),
    );

    /**
     * Check if this cart rule can be applied
     *
     * @param Context $context
     * @param bool $alreadyInCart Check if the voucher is already on the cart
     * @param bool $display_error Display error
     * @return bool|mixed|string
     */
    public function checkValidity(Context $context, $alreadyInCart = false, $display_error = true, $check_carrier = true)
    {
        if (!CartRule::isFeatureActive())
            return false;

        if (!$this->active)
            return (!$display_error) ? false : Tools::displayError('This voucher is disabled');
        if (!$this->quantity)
            return (!$display_error) ? false : Tools::displayError('This voucher has already been used');
        if (strtotime($this->date_from) > time())
            return (!$display_error) ? false : Tools::displayError('This voucher is not valid yet');
        if (strtotime($this->date_to) < time())
            return (!$display_error) ? false : Tools::displayError('This voucher has expired');

        if ($context->cart->id_customer)
        {
            $quantityUsed = Db::getInstance()->getValue('
            SELECT count(*)
            FROM '._DB_PREFIX_.'orders o
            LEFT JOIN '._DB_PREFIX_.'order_cart_rule od ON o.id_order = od.id_order
            WHERE o.id_customer = '.$context->cart->id_customer.'
            AND od.id_cart_rule = '.(int)$this->id.'
            AND '.(int)Configuration::get('PS_OS_ERROR').' != o.current_state
            ');
            if ($quantityUsed + 1 > $this->quantity_per_user)
                return (!$display_error) ? false : Tools::displayError('You cannot use this voucher anymore (usage limit reached)');
        }

        // Get an intersection of the customer groups and the cart rule groups (if the customer is not logged in, the default group is Visitors)
        if ($this->group_restriction)
        {
            $id_cart_rule = (int)Db::getInstance()->getValue('
            SELECT crg.id_cart_rule
            FROM '._DB_PREFIX_.'cart_rule_group crg
            WHERE crg.id_cart_rule = '.(int)$this->id.'
            AND crg.id_group '.($context->cart->id_customer ? 'IN (SELECT cg.id_group FROM '._DB_PREFIX_.'customer_group cg WHERE cg.id_customer = '.(int)$context->cart->id_customer.')' : '= '.(int)Configuration::get('PS_UNIDENTIFIED_GROUP')));
            if (!$id_cart_rule)
                return (!$display_error) ? false : Tools::displayError('You cannot use this voucher');
        }

        // Check if the customer delivery address is usable with the cart rule
        if ($this->country_restriction)
        {
            if (!$context->cart->id_address_delivery)
                return (!$display_error) ? false : Tools::displayError('You must choose a delivery address before applying this voucher to your order');
            $id_cart_rule = (int)Db::getInstance()->getValue('
            SELECT crc.id_cart_rule
            FROM '._DB_PREFIX_.'cart_rule_country crc
            WHERE crc.id_cart_rule = '.(int)$this->id.'
            AND crc.id_country = (SELECT a.id_country FROM '._DB_PREFIX_.'address a WHERE a.id_address = '.(int)$context->cart->id_address_delivery.' LIMIT 1)');
            if (!$id_cart_rule)
                return (!$display_error) ? false : Tools::displayError('You cannot use this voucher in your country of delivery');
        }

        // Check if the carrier chosen by the customer is usable with the cart rule
        if ($this->carrier_restriction && $check_carrier)
        {
            if (!$context->cart->id_carrier)
                return (!$display_error) ? false : Tools::displayError('You must choose a carrier before applying this voucher to your order');
            $id_cart_rule = (int)Db::getInstance()->getValue('
            SELECT crc.id_cart_rule
            FROM '._DB_PREFIX_.'cart_rule_carrier crc
            INNER JOIN '._DB_PREFIX_.'carrier c ON (c.id_reference = crc.id_carrier AND c.deleted = 0)
            WHERE crc.id_cart_rule = '.(int)$this->id.'
            AND c.id_carrier = '.(int)$context->cart->id_carrier);
            if (!$id_cart_rule)
                return (!$display_error) ? false : Tools::displayError('You cannot use this voucher with this carrier');
        }

        // Check if the cart rules appliy to the shop browsed by the customer
        if ($this->shop_restriction && $context->shop->id && Shop::isFeatureActive())
        {
            $id_cart_rule = (int)Db::getInstance()->getValue('
            SELECT crs.id_cart_rule
            FROM '._DB_PREFIX_.'cart_rule_shop crs
            WHERE crs.id_cart_rule = '.(int)$this->id.'
            AND crs.id_shop = '.(int)$context->shop->id);
            if (!$id_cart_rule)
                return (!$display_error) ? false : Tools::displayError('You cannot use this voucher');
        }

        // Check if the products chosen by the customer are usable with the cart rule
        if ($this->product_restriction)
        {
            $r = $this->checkProductRestrictions($context, false, $display_error, $alreadyInCart);
            if ($r !== false && $display_error)
                return $r;
            elseif (!$r && !$display_error)
                return false;
        }

        // Check if the cart rule is only usable by a specific customer, and if the current customer is the right one
        if ($this->id_customer && $context->cart->id_customer != $this->id_customer)
        {
            if (!Context::getContext()->customer->isLogged())
                return (!$display_error) ? false : (Tools::displayError('You cannot use this voucher').' - '.Tools::displayError('Please log in first'));
            return (!$display_error) ? false : Tools::displayError('You cannot use this voucher');
        }

        if ($this->minimum_amount && $check_carrier)
        {
            // Minimum amount is converted to the contextual currency
            $minimum_amount = $this->minimum_amount;
            if ($this->minimum_amount_currency != Context::getContext()->currency->id)
                $minimum_amount = Tools::convertPriceFull($minimum_amount, new Currency($this->minimum_amount_currency), Context::getContext()->currency);

            $cartTotal = $context->cart->getOrderTotal($this->minimum_amount_tax, Cart::ONLY_PRODUCTS);
            if ($this->minimum_amount_shipping)
                $cartTotal += $context->cart->getOrderTotal($this->minimum_amount_tax, Cart::ONLY_SHIPPING);
            $products = $context->cart->getProducts();
            $cart_rules = $context->cart->getCartRules();

            foreach ($cart_rules as &$cart_rule)
                if ($cart_rule['gift_product'])
                    foreach ($products as $key => &$product)
                        if (empty($product['gift']) && $product['id_product'] == $cart_rule['gift_product'] && $product['id_product_attribute'] == $cart_rule['gift_product_attribute'])
                            $cartTotal = Tools::ps_round($cartTotal - $product[$this->minimum_amount_tax ? 'price_wt' : 'price'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);

            if ($cartTotal < $minimum_amount)
                return (!$display_error) ? false : Tools::displayError('You have not reached the minimum amount required to use this voucher');
        }

        // Check if cart rule is discount card (real or virtual) and current user is logged in and user have accumulated needed order sum
        if ($this->is_discount_cart_rule)
        {
            if (!Context::getContext()->customer->isLogged())
                return (!$display_error) ? false : (Tools::displayError('You cannot use this voucher').' - '.Tools::displayError('Please log in first'));
            if (!$discount_card = Context::getContext()->customer->getDiscountCard())
            {
                return (!$display_error) ? false : (Tools::displayError('You cannot use this voucher').' - '.Tools::displayError('You have not assigned discount cart'));
            }
            else
            {
                if ($this->accumulated_minimum_amount >= $discount_card->getAccumulatedSum())
                    return (!$display_error) ? false : (Tools::displayError('You cannot use this voucher').' - '.Tools::displayError('You have not accumulated needed orders total sum to use this discount'));
            }
        }

        /* This loop checks:
            - if the voucher is already in the cart
            - if a non compatible voucher is in the cart
            - if there are products in the cart (gifts excluded)
            Important note: this MUST be the last check, because if the tested cart rule has priority over a non combinable one in the cart, we will switch them
        */
        $nb_products = Cart::getNbProducts($context->cart->id);
        $otherCartRules = array();
        if ($check_carrier)
            $otherCartRules = $context->cart->getCartRules();
        if (count($otherCartRules))
            foreach ($otherCartRules as $otherCartRule)
            {
                if ($otherCartRule['id_cart_rule'] == $this->id && !$alreadyInCart)
                    return (!$display_error) ? false : Tools::displayError('This voucher is already in your cart');
                if ($otherCartRule['gift_product'])
                    --$nb_products;

                if ($this->cart_rule_restriction && $otherCartRule['cart_rule_restriction'] && $otherCartRule['id_cart_rule'] != $this->id)
                {
                    $combinable = Db::getInstance()->getValue('
                    SELECT id_cart_rule_1
                    FROM '._DB_PREFIX_.'cart_rule_combination
                    WHERE (id_cart_rule_1 = '.(int)$this->id.' AND id_cart_rule_2 = '.(int)$otherCartRule['id_cart_rule'].')
                    OR (id_cart_rule_2 = '.(int)$this->id.' AND id_cart_rule_1 = '.(int)$otherCartRule['id_cart_rule'].')');
                    if (!$combinable)
                    {
                        $cart_rule = new CartRule((int)$otherCartRule['id_cart_rule'], $context->cart->id_lang);
                        // The cart rules are not combinable and the cart rule currently in the cart has priority over the one tested
                        if ($cart_rule->priority <= $this->priority)
                            return (!$display_error) ? false : Tools::displayError('This voucher is not combinable with an other voucher already in your cart:').' '.$cart_rule->name;
                        // But if the cart rule that is tested has priority over the one in the cart, we remove the one in the cart and keep this new one
                        else
                            $context->cart->removeCartRule($cart_rule->id);
                    }
                }
            }

        if (!$nb_products)
            return (!$display_error) ? false : Tools::displayError('Cart is empty');

        if (!$display_error)
            return true;
    }

    /**
     * @static
     * @param Context|null $context
     * @return mixed
     */
    public static function autoAddToCart(Context $context = null)
    {
        if ($context === null)
            $context = Context::getContext();
        if (!CartRule::isFeatureActive() || !Validate::isLoadedObject($context->cart))
            return;

        $sql = '
        SELECT cr.*
        FROM '._DB_PREFIX_.'cart_rule cr
        LEFT JOIN '._DB_PREFIX_.'cart_rule_shop crs ON cr.id_cart_rule = crs.id_cart_rule
        '.(!$context->customer->id && Group::isFeatureActive() ? ' LEFT JOIN '._DB_PREFIX_.'cart_rule_group crg ON cr.id_cart_rule = crg.id_cart_rule' : '').'
        LEFT JOIN '._DB_PREFIX_.'cart_rule_carrier crca ON cr.id_cart_rule = crca.id_cart_rule
        '.($context->cart->id_carrier ? 'LEFT JOIN '._DB_PREFIX_.'carrier c ON (c.id_reference = crca.id_carrier AND c.deleted = 0)' : '').'
        LEFT JOIN '._DB_PREFIX_.'cart_rule_country crco ON cr.id_cart_rule = crco.id_cart_rule
        WHERE cr.active = 1
        AND cr.code = ""
        AND cr.quantity > 0
        AND cr.date_from < "'.date('Y-m-d H:i:s').'"
        AND cr.date_to > "'.date('Y-m-d H:i:s').'"
        AND (
            cr.id_customer = 0
            '.($context->customer->id ? 'OR cr.id_customer = '.(int)$context->cart->id_customer : '').'
        )
        AND (
            cr.`carrier_restriction` = 0
            '.($context->cart->id_carrier ? 'OR c.id_carrier = '.(int)$context->cart->id_carrier : '').'
        )
        AND (
            cr.`shop_restriction` = 0
            '.((Shop::isFeatureActive() && $context->shop->id) ? 'OR crs.id_shop = '.(int)$context->shop->id : '').'
        )
        AND (
            cr.`group_restriction` = 0
            '.($context->customer->id ? 'OR 0 < (
                SELECT cg.`id_group`
                FROM `'._DB_PREFIX_.'customer_group` cg
                INNER JOIN `'._DB_PREFIX_.'cart_rule_group` crg ON cg.id_group = crg.id_group
                WHERE cr.`id_cart_rule` = crg.`id_cart_rule`
                AND cg.`id_customer` = '.(int)$context->customer->id.'
                LIMIT 1
            )' : (Group::isFeatureActive() ? 'OR crg.`id_group` = '.(int)Configuration::get('PS_UNIDENTIFIED_GROUP') : '')).'
        )
        AND (
            cr.`reduction_product` <= 0
            OR cr.`reduction_product` IN (
                SELECT `id_product`
                FROM `'._DB_PREFIX_.'cart_product`
                WHERE `id_cart` = '.(int)$context->cart->id.'
            )
        )
        AND cr.id_cart_rule NOT IN (SELECT id_cart_rule FROM '._DB_PREFIX_.'cart_cart_rule WHERE id_cart = '.(int)$context->cart->id.')
        ORDER BY priority';

        $result = Db::getInstance()->executeS($sql);

        if ($result)
        {
            $cart_rules = ObjectModel::hydrateCollection('CartRule', $result);
            if ($cart_rules)
                foreach ($cart_rules as $cart_rule) {
                    if ($cart_rule->checkValidity($context, false, false))
                        $context->cart->addCartRule($cart_rule->id);
                }
        }

        $context->cart->applyMaxCartRuleDiscount();
    }



    /**
     * The reduction value is POSITIVE
     *
     * @param bool $use_tax
     * @param Context $context
     * @param boolean $use_cache Allow using cache to avoid multiple free gift using multishipping
     * @return float|int|string
     */
    public function getContextualValue($use_tax, Context $context = null, $filter = null, $package = null, $use_cache = true)
    {
        if (!CartRule::isFeatureActive())
            return 0;
        if (!$context)
            $context = Context::getContext();
        if (!$filter)
            $filter = CartRule::FILTER_ACTION_ALL;

        $all_products = $context->cart->getProducts();
        $package_products = (is_null($package) ? $all_products : $package['products']);

        foreach($package_products as $key => $product)
            if (Product::isDiscounted($product['id_product']))
                unset($package_products[$key]);

        $reduction_value = 0;

        $cache_id = 'getContextualValue_'.(int)$this->id.'_'.(int)$use_tax.'_'.(int)$context->cart->id.'_'.(int)$filter;
        foreach ($package_products as $product)
            $cache_id .= '_'.(int)$product['id_product'].'_'.(int)$product['id_product_attribute'];

        if (Cache::isStored($cache_id))
            return Cache::retrieve($cache_id);

        // Free shipping on selected carriers
        if ($this->free_shipping && in_array($filter, array(CartRule::FILTER_ACTION_ALL, CartRule::FILTER_ACTION_ALL_NOCAP, CartRule::FILTER_ACTION_SHIPPING)))
        {
            if (!$this->carrier_restriction)
                $reduction_value += $context->cart->getOrderTotal($use_tax, Cart::ONLY_SHIPPING, is_null($package) ? null : $package['products'], is_null($package) ? null : $package['id_carrier']);
            else
            {
                $data = Db::getInstance()->executeS('
                    SELECT crc.id_cart_rule, c.id_carrier
                    FROM '._DB_PREFIX_.'cart_rule_carrier crc
                    INNER JOIN '._DB_PREFIX_.'carrier c ON (c.id_reference = crc.id_carrier AND c.deleted = 0)
                    WHERE crc.id_cart_rule = '.(int)$this->id.'
                    AND c.id_carrier = '.(int)$context->cart->id_carrier);

                if ($data)
                    foreach ($data as $cart_rule)
                        $reduction_value += $context->cart->getCarrierCost((int)$cart_rule['id_carrier'], $use_tax, $context->country);
            }
        }

        if (in_array($filter, array(CartRule::FILTER_ACTION_ALL, CartRule::FILTER_ACTION_ALL_NOCAP, CartRule::FILTER_ACTION_REDUCTION)))
        {
            // Discount (%) on the whole order
            if ($this->reduction_percent && $this->reduction_product == 0)
            {
                // Do not give a reduction on free products!
                $order_total = $context->cart->getOrderTotal($use_tax, Cart::ONLY_PRODUCTS, $package_products);
                foreach ($context->cart->getCartRules(CartRule::FILTER_ACTION_GIFT) as $cart_rule)
                    $order_total -= Tools::ps_round($cart_rule['obj']->getContextualValue($use_tax, $context, CartRule::FILTER_ACTION_GIFT, $package), _PS_PRICE_COMPUTE_PRECISION_);

                $reduction_value += $order_total * $this->reduction_percent / 100;
            }

            // Discount (%) on a specific product
            if ($this->reduction_percent && $this->reduction_product > 0)
            {
                foreach ($package_products as $product)
                    if ($product['id_product'] == $this->reduction_product)
                        $reduction_value += ($use_tax ? $product['total_wt'] : $product['total']) * $this->reduction_percent / 100;
            }

            // Discount (%) on the cheapest product
            if ($this->reduction_percent && $this->reduction_product == -1)
            {
                $minPrice = false;
                $cheapest_product = null;
                foreach ($all_products as $product)
                {
                    $price = ($use_tax ? $product['price_wt'] : $product['price']);
                    if ($price > 0 && ($minPrice === false || $minPrice > $price))
                    {
                        $minPrice = $price;
                        $cheapest_product = $product['id_product'].'-'.$product['id_product_attribute'];
                    }
                }

                // Check if the cheapest product is in the package
                $in_package = false;
                foreach ($package_products as $product)
                    if ($product['id_product'].'-'.$product['id_product_attribute'] == $cheapest_product || $product['id_product'].'-0' == $cheapest_product)
                        $in_package = true;
                if ($in_package)
                    $reduction_value += $minPrice * $this->reduction_percent / 100;
            }

            // Discount (%) on the selection of products
            if ($this->reduction_percent && $this->reduction_product == -2)
            {
                $selected_products_reduction = 0;
                $selected_products = $this->checkProductRestrictions($context, true);
                if (is_array($selected_products))
                    foreach ($package_products as $product)
                        if (in_array($product['id_product'].'-'.$product['id_product_attribute'], $selected_products)
                            || in_array($product['id_product'].'-0', $selected_products))
                        {
                            $price = ($use_tax ? $product['price_wt'] : $product['price']);
                            $selected_products_reduction += $price * $product['cart_quantity'];
                        }
                $reduction_value += $selected_products_reduction * $this->reduction_percent / 100;
            }

            // Discount (¤)
            if ($this->reduction_amount)
            {
                $prorata = 1;
                if (!is_null($package) && count($all_products))
                {
                    $total_products = $context->cart->getOrderTotal($use_tax, Cart::ONLY_PRODUCTS);
                    if ($total_products)
                        $prorata = $context->cart->getOrderTotal($use_tax, Cart::ONLY_PRODUCTS, $package['products']) / $total_products;
                }

                $reduction_amount = $this->reduction_amount;
                // If we need to convert the voucher value to the cart currency
                if (isset($context->currency) && $this->reduction_currency != $context->currency->id)
                {
                    $voucherCurrency = new Currency($this->reduction_currency);

                    // First we convert the voucher value to the default currency
                    if ($reduction_amount == 0 || $voucherCurrency->conversion_rate == 0)
                        $reduction_amount = 0;
                    else
                        $reduction_amount /= $voucherCurrency->conversion_rate;

                    // Then we convert the voucher value in the default currency into the cart currency
                    $reduction_amount *= $context->currency->conversion_rate;
                    $reduction_amount = Tools::ps_round($reduction_amount, _PS_PRICE_COMPUTE_PRECISION_);
                }

                // If it has the same tax application that you need, then it's the right value, whatever the product!
                if ($this->reduction_tax == $use_tax)
                {
                    // The reduction cannot exceed the products total, except when we do not want it to be limited (for the partial use calculation)
                    if ($filter != CartRule::FILTER_ACTION_ALL_NOCAP)
                    {
                        $cart_amount = $context->cart->getOrderTotal($use_tax, Cart::ONLY_PRODUCTS);
                        $reduction_amount = min($reduction_amount, $cart_amount);
                    }
                    $reduction_value += $prorata * $reduction_amount;
                }
                else
                {
                    if ($this->reduction_product > 0)
                    {
                        foreach ($context->cart->getProducts() as $product)
                            if ($product['id_product'] == $this->reduction_product)
                            {
                                $product_price_ti = $product['price_wt'];
                                $product_price_te = $product['price'];
                                $product_vat_amount = $product_price_ti - $product_price_te;

                                if ($product_vat_amount == 0 || $product_price_te == 0)
                                    $product_vat_rate = 0;
                                else
                                    $product_vat_rate = $product_vat_amount / $product_price_te;

                                if ($this->reduction_tax && !$use_tax)
                                    $reduction_value += $prorata * $reduction_amount / (1 + $product_vat_rate);
                                elseif (!$this->reduction_tax && $use_tax)
                                    $reduction_value += $prorata * $reduction_amount * (1 + $product_vat_rate);
                            }
                    }
                    // Discount (¤) on the whole order
                    elseif ($this->reduction_product == 0)
                    {
                        $cart_amount_ti = $context->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
                        $cart_amount_te = $context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);

                        // The reduction cannot exceed the products total, except when we do not want it to be limited (for the partial use calculation)
                        if ($filter != CartRule::FILTER_ACTION_ALL_NOCAP)
                            $reduction_amount = min($reduction_amount, $this->reduction_tax ? $cart_amount_ti : $cart_amount_te);

                        $cart_vat_amount = $cart_amount_ti - $cart_amount_te;

                        if ($cart_vat_amount == 0 || $cart_amount_te == 0)
                            $cart_average_vat_rate = 0;
                        else
                            $cart_average_vat_rate = Tools::ps_round($cart_vat_amount / $cart_amount_te, 3);

                        if ($this->reduction_tax && !$use_tax)
                            $reduction_value += $prorata * $reduction_amount / (1 + $cart_average_vat_rate);
                        elseif (!$this->reduction_tax && $use_tax)
                            $reduction_value += $prorata * $reduction_amount * (1 + $cart_average_vat_rate);
                    }
                    /*
                     * Reduction on the cheapest or on the selection is not really meaningful and has been disabled in the backend
                     * Please keep this code, so it won't be considered as a bug
                     * elseif ($this->reduction_product == -1)
                     * elseif ($this->reduction_product == -2)
                    */
                }
            }
        }

        // Free gift
        if ((int)$this->gift_product && in_array($filter, array(CartRule::FILTER_ACTION_ALL, CartRule::FILTER_ACTION_ALL_NOCAP, CartRule::FILTER_ACTION_GIFT)))
        {
            $id_address = (is_null($package) ? 0 : $package['id_address']);
            foreach ($package_products as $product)
                if ($product['id_product'] == $this->gift_product && ($product['id_product_attribute'] == $this->gift_product_attribute || !(int)$this->gift_product_attribute))
                {
                    // The free gift coupon must be applied to one product only (needed for multi-shipping which manage multiple product lists)
                    if (!isset(CartRule::$only_one_gift[$this->id.'-'.$this->gift_product])
                        || CartRule::$only_one_gift[$this->id.'-'.$this->gift_product] == $id_address
                        || CartRule::$only_one_gift[$this->id.'-'.$this->gift_product] == 0
                        || $id_address == 0
                        || !$use_cache)
                    {
                        $reduction_value += ($use_tax ? $product['price_wt'] : $product['price']);
                        if ($use_cache && (!isset(CartRule::$only_one_gift[$this->id.'-'.$this->gift_product]) || CartRule::$only_one_gift[$this->id.'-'.$this->gift_product] == 0))
                            CartRule::$only_one_gift[$this->id.'-'.$this->gift_product] = $id_address;
                        break;
                    }
                }
        }

        Cache::store($cache_id, $reduction_value);
        return $reduction_value;
    }
}
