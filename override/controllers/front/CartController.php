<?php

class CartController extends CartControllerCore
{
    /**
     * Display ajax content (this function is called instead of classic display, in ajax mode)
     */
    public function displayAjax()
    {
        CartRule::autoRemoveFromCart($this->context);
        CartRule::autoAddToCart($this->context);

        if ($this->errors)
            die(Tools::jsonEncode(array('hasError' => true, 'errors' => $this->errors)));
        // if ($this->ajax_refresh)
        //     die(Tools::jsonEncode(array('refresh' => true)));

        // write cookie if can't on destruct
        $this->context->cookie->write();

        if (Tools::getIsset('summary'))
        {
            $result = array();
            if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1)
            {
                $groups = (Validate::isLoadedObject($this->context->customer)) ? $this->context->customer->getGroups() : array(1);
                if ($this->context->cart->id_address_delivery)
                    $deliveryAddress = new Address($this->context->cart->id_address_delivery);
                $id_country = (isset($deliveryAddress) && $deliveryAddress->id) ? (int)$deliveryAddress->id_country : (int)Tools::getCountry();

                Cart::addExtraCarriers($result);
            }
            $result['summary'] = $this->context->cart->getSummaryDetails(null, true);
            $result['customizedDatas'] = Product::getAllCustomizedDatas($this->context->cart->id, null, true);
            // $result['HOOK_SHOPPING_CART'] = Hook::exec('displayShoppingCartFooter', $result['summary']);
            // $result['HOOK_SHOPPING_CART_EXTRA'] = Hook::exec('displayShoppingCart', $result['summary']);

            foreach ($result['summary']['products'] as $key => &$product)
            {
                $product['quantity_without_customization'] = $product['quantity'];
                if ($result['customizedDatas'] && isset($result['customizedDatas'][(int)$product['id_product']][(int)$product['id_product_attribute']]))
                {
                    foreach ($result['customizedDatas'][(int)$product['id_product']][(int)$product['id_product_attribute']] as $addresses)
                        foreach ($addresses as $customization)
                            $product['quantity_without_customization'] -= (int)$customization['quantity'];
                }
                $product['price_without_quantity_discount'] = Product::getPriceStatic(
                    $product['id_product'],
                    !Product::getTaxCalculationMethod(),
                    $product['id_product_attribute'],
                    6,
                    null,
                    false,
                    false
                );

                if ($product['reduction_type'] == 'amount')
                {
                    $reduction = (float)$product['price_wt'] - (float)$product['price_without_quantity_discount'];
                    $product['reduction_formatted'] = Tools::displayPrice($reduction);
                }
            }
            if ($result['customizedDatas'])
                Product::addCustomizationPrice($result['summary']['products'], $result['customizedDatas']);

            $this->context->smarty->assign('products', $result['summary']['products']);
            $this->context->smarty->assign('customizedDatas', $result['customizedDatas']);
            $this->context->smarty->assign('token_cart', Tools::getToken(false));
            $this->context->smarty->assign('discounts', $this->context->cart->getCartRules());
            $result['shopping_cart_tbody'] = $this->context->smarty->fetch(_PS_THEME_DIR_.'shopping-cart-tbody.tpl');

            // Get product comments data
            require_once(_PS_MODULE_DIR_.'productcomments/ProductComment.php');
            $result['comments'][(int)$product['id_product']] = array(
                'averageTotal' => round(ProductComment::getAverageGrade($product['id_product'])['grade']),
                'ratings' => ProductComment::getRatings($product['id_product']),
                'nbComments' => (int)ProductComment::getCommentNumber($product['id_product'])
            );

            Hook::exec('actionCartListOverride', array('summary' => $result, 'json' => &$json));
            $results = array_merge($result, (array)Tools::jsonDecode($json, true));
            // Get crosselings with summary and merge data arrays
            include_once(_PS_MODULE_DIR_.'/blockcart/blockcart.php');
            $context = Context::getContext();
            $blockCart = new BlockCart();
            $crossSelling = $blockCart->hookAjaxCall(array('cookie' => $context->cookie, 'cart' => $context->cart));
            die(Tools::jsonEncode(array_merge($results, (array)Tools::jsonDecode($crossSelling, true))));
        }
        // @todo create a hook
        elseif (file_exists(_PS_MODULE_DIR_.'/blockcart/blockcart-ajax.php'))
            require_once(_PS_MODULE_DIR_.'/blockcart/blockcart-ajax.php');
    }

    /**
     * This process add or update a product in the cart
     */
    protected function processChangeProductInCart()
    {
        $mode = (Tools::getIsset('update') && $this->id_product) ? 'update' : 'add';

        if ($this->qty == 0)
            $this->errors[] = Tools::displayError('Null quantity.', !Tools::getValue('ajax'));
        elseif (!$this->id_product)
            $this->errors[] = Tools::displayError('Product not found', !Tools::getValue('ajax'));

        $product = new Product($this->id_product, true, $this->context->language->id);
        if (!$product->id || !$product->active)
        {
            $this->errors[] = Tools::displayError('This product is no longer available.', !Tools::getValue('ajax'));
            return;
        }

        $qty_to_check = $this->qty;
        $cart_products = $this->context->cart->getProducts();

        if (is_array($cart_products))
            foreach ($cart_products as $cart_product)
            {
                if ((!isset($this->id_product_attribute) || $cart_product['id_product_attribute'] == $this->id_product_attribute) &&
                    (isset($this->id_product) && $cart_product['id_product'] == $this->id_product))
                {
                    $qty_to_check = $cart_product['cart_quantity'];

                    if (Tools::getValue('op', 'up') == 'down')
                        $qty_to_check -= $this->qty;
                    else
                        $qty_to_check += $this->qty;

                    break;
                }
            }

        // Check product quantity availability
        if ($this->id_product_attribute)
        {
            if (!Product::isAvailableWhenOutOfStock($product->out_of_stock) && !Attribute::checkAttributeQty($this->id_product_attribute, $qty_to_check))
                $this->errors[] = Tools::displayError('There isn\'t enough product in stock.', !Tools::getValue('ajax'));
        }
        elseif ($product->hasAttributes())
        {
            $minimumQuantity = ($product->out_of_stock == 2) ? !Configuration::get('PS_ORDER_OUT_OF_STOCK') : !$product->out_of_stock;
            $this->id_product_attribute = Product::getDefaultAttribute($product->id, $minimumQuantity);
            // @todo do something better than a redirect admin !!
            if (!$this->id_product_attribute)
                Tools::redirectAdmin($this->context->link->getProductLink($product));
            elseif (!Product::isAvailableWhenOutOfStock($product->out_of_stock) && !Attribute::checkAttributeQty($this->id_product_attribute, $qty_to_check))
                $this->errors[] = Tools::displayError('There isn\'t enough product in stock.', !Tools::getValue('ajax'));
        }
        elseif (!$product->checkQty($qty_to_check))
            $this->errors[] = Tools::displayError('There isn\'t enough product in stock.', !Tools::getValue('ajax'));

        // If no errors, process product addition
        if (!$this->errors && $mode == 'add')
        {
            // Add cart if no cart found
            if (!$this->context->cart->id)
            {
                if (Context::getContext()->cookie->id_guest)
                {
                    $guest = new Guest(Context::getContext()->cookie->id_guest);
                    $this->context->cart->mobile_theme = $guest->mobile_theme;
                }
                $this->context->cart->add();
                if ($this->context->cart->id)
                    $this->context->cookie->id_cart = (int)$this->context->cart->id;
            }

            // Check customizable fields
            if (!$product->hasAllRequiredCustomizableFields() && !$this->customization_id)
                $this->errors[] = Tools::displayError('Please fill in all of the required fields, and then save your customizations.', !Tools::getValue('ajax'));

            if (!$this->errors)
            {
                $cart_rules = $this->context->cart->getCartRules();
                $update_quantity = $this->context->cart->updateQty($this->qty, $this->id_product, $this->id_product_attribute, $this->customization_id, Tools::getValue('op', 'up'), $this->id_address_delivery);
                if ($update_quantity < 0)
                {
                    // If product has attribute, minimal quantity is set with minimal quantity of attribute
                    $minimal_quantity = ($this->id_product_attribute) ? Attribute::getAttributeMinimalQty($this->id_product_attribute) : $product->minimal_quantity;
                    $this->errors[] = sprintf(Tools::displayError('You must add %d minimum quantity', !Tools::getValue('ajax')), $minimal_quantity);
                }
                elseif (!$update_quantity)
                    $this->errors[] = Tools::displayError('You already have the maximum quantity available for this product.', !Tools::getValue('ajax'));
                elseif ((int)Tools::getValue('allow_refresh'))
                {
                    // If the cart rules has changed, we need to refresh the whole cart
                    $cart_rules2 = $this->context->cart->getCartRules();
                    if (count($cart_rules2) != count($cart_rules)) {
                        // $this->ajax_refresh = true;
                    }
                    else
                    {
                        $rule_list = array();
                        foreach ($cart_rules2 as $rule)
                            $rule_list[] = $rule['id_cart_rule'];
                        foreach ($cart_rules as $rule)
                            if (!in_array($rule['id_cart_rule'], $rule_list))
                            {
                                // $this->ajax_refresh = true;
                                break;
                            }
                    }
                }
            }
        }

        $removed = CartRule::autoRemoveFromCart();
        CartRule::autoAddToCart();
        if (count($removed) && (int)Tools::getValue('allow_refresh'))
            $this->ajax_refresh = true;
    }
}
