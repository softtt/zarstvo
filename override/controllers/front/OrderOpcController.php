<?php

/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class OrderOpcController extends OrderOpcControllerCore
{
    /**
     * Initialize order opc controller
     * @see FrontController::init()
     */
    const __MA_MAIL_DELIMITOR__ = "\n";

    public function init()
    {
        ParentOrderController::init();
        if ($this->nbProducts)
            $this->context->smarty->assign('virtual_cart', $this->context->cart->isVirtualCart());

        $this->context->smarty->assign('is_multi_address_delivery', $this->context->cart->isMultiAddressDelivery() || ((int)Tools::getValue('multi-shipping') == 1));
        $this->context->smarty->assign('open_multishipping_fancybox', (int)Tools::getValue('multi-shipping') == 1);

        //customer order denial
        if (Tools::isSubmit('denyOrder') && $id_order = (int)Tools::getValue('id_order')) {
            $order_denied_state_id = _PS_OS_CANCELED_;
            $order_state = new OrderState($order_denied_state_id);
            $context = Context::getContext();
            $id_lang = (int)$context->language->id;
            $id_shop = (int)$context->shop->id;
            $order = new Order((int)$id_order);

            $current_order_state = $order->getCurrentOrderState();
            if ($current_order_state->id == $order_state->id)
                $this->errors[] = $this->displayWarning(sprintf('Order #%d has already been assigned this status.', $id_order));
            else {
                $history = new OrderHistory();
                $history->id_order = $order->id;
                $history->id_employee = (int)$this->context->employee->id;

                $use_existings_payment = !$order->hasInvoice();
                $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);

                //send email to merchant
                $merchant_mails = str_replace(',', self::__MA_MAIL_DELIMITOR__, (string)Configuration::get('MA_MERCHANT_MAILS'));
                $merchant_mails = explode(self::__MA_MAIL_DELIMITOR__, $merchant_mails);

                if (file_exists(_PS_MAIL_DIR_ . '/ru' . '/customer_order_denial.html'))
                    $dir_mail = _PS_MAIL_DIR_;

                $configuration = Configuration::getMultiple(
                    array(
                        'PS_SHOP_EMAIL',
                        'PS_SHOP_NAME',
                    ), $id_lang, null, $id_shop
                );
                $template_vars = array(
                    '{order_name}' => (int)$order->id,
                    '{order_status}' => $order_state->name,
                    '{order_reference}' => $order->reference,
                );
                foreach ($merchant_mails as $merchant_mail) {
                    if ($dir_mail)
                        Mail::Send((int)$order->id_lang, 'customer_order_denial', 'Отмена заказа клиентом', $template_vars, $merchant_mail, null,
                            null, null, null, null, _PS_MAIL_DIR_, false, (int)$order->id_shop);
                }


                $carrier = new Carrier($order->id_carrier, $order->id_lang);
                $templateVars = array();
                if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number)
                    $templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));

                if ($history->addWithemail(true, $templateVars)) {
                    if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'))
                        foreach ($order->getProducts() as $product)
                            if (StockAvailable::dependsOnStock($product['product_id']))
                                StockAvailable::synchronize($product['product_id'], (int)$product['id_shop']);
                } else
                    $this->errors[] = sprintf(Tools::displayError('Cannot change status for order #%d.'), $id_order);
            }
            $link = $this->context->link;
            Tools::redirect($link->getPageLink('history', true));
        }

        if ($this->context->cart->getOrderTotal(false) == 0) {
            if (Tools::isSubmit('ajax')) {
                $this->errors[] = Tools::displayError('Товаров нет в наличии.');
                die('{"hasError" : true, "errors" : ["' . implode('\',\'', $this->errors) . '"]}');
            }
        } elseif ($this->context->cart->nbProducts()) {
            if (Tools::isSubmit('ajax')) {

                if (Tools::isSubmit('method')) {

                    switch (Tools::getValue('method')) {
                        case 'updateMessage':
                            if (Tools::isSubmit('message')) {
                                $txtMessage = urldecode(Tools::getValue('message'));
                                $this->_updateMessage($txtMessage);
                                if (count($this->errors))
                                    die('{"hasError" : true, "errors" : ["' . implode('\',\'', $this->errors) . '"]}');
                                die(true);
                            }
                            break;

                        case 'updateCarrierAndGetPayments':
                            if ((Tools::isSubmit('delivery_option') || Tools::isSubmit('id_carrier'))
                                && Tools::isSubmit('recyclable')
                                && Tools::isSubmit('gift')
                                && Tools::isSubmit('gift_message')
                            ) {
                                $this->_assignWrappingAndTOS();
                                if ($this->_processCarrier()) {
                                    $carriers = $this->context->cart->simulateCarriersOutput();
                                    $return = array_merge(array(
                                        'HOOK_TOP_PAYMENT' => Hook::exec('displayPaymentTop'),
                                        'HOOK_PAYMENT' => $this->_getPaymentMethods(),
                                        'carrier_data' => $this->_getCarrierList(),
                                        'HOOK_BEFORECARRIER' => Hook::exec('displayBeforeCarrier', array('carriers' => $carriers))
                                    ),
                                        $this->getFormatedSummaryDetail()
                                    );
                                    Cart::addExtraCarriers($return);
                                    die(Tools::jsonEncode($return));
                                } else
                                    $this->errors[] = Tools::displayError('An error occurred while updating the cart.');
                                if (count($this->errors))
                                    die('{"hasError" : true, "errors" : ["' . implode('\',\'', $this->errors) . '"]}');
                                exit;
                            }
                            break;

                        case 'updateTOSStatusAndGetPayments':
                            if (Tools::isSubmit('checked')) {
                                $this->context->cookie->checkedTOS = (int)Tools::getValue('checked');
                                die(Tools::jsonEncode(array(
                                    'HOOK_TOP_PAYMENT' => Hook::exec('displayPaymentTop'),
                                    'HOOK_PAYMENT' => $this->_getPaymentMethods()
                                )));
                            }
                            break;

                        case 'getCarrierList':
                            die(Tools::jsonEncode($this->_getCarrierList()));
                            break;

                        case 'editCustomer':
                            if (!$this->isLogged)
                                exit;

                            $_POST['lastname'] = $_POST['customer_lastname'];
                            $_POST['firstname'] = $_POST['customer_firstname'];
                            $this->errors = array_merge($this->errors, $this->context->customer->validateController());
                            $this->context->customer->newsletter = (int)Tools::isSubmit('newsletter');
                            $this->context->customer->optin = (int)Tools::isSubmit('optin');
                            $return = array(
                                'hasError' => !empty($this->errors),
                                'errors' => $this->errors,
                                'id_customer' => (int)$this->context->customer->id,
                                'token' => Tools::getToken(false)
                            );
                            if (!count($this->errors))
                                $return['isSaved'] = (bool)$this->context->customer->update();
                            else
                                $return['isSaved'] = false;
                            die(Tools::jsonEncode($return));
                            break;

                        case 'getAddressBlockAndCarriersAndPayments':
                            if ($this->context->customer->isLogged()) {
                                // check if customer have addresses
                                if (!Customer::getAddressesTotalById($this->context->customer->id))
                                    die(Tools::jsonEncode(array('no_address' => 1)));
                                if (file_exists(_PS_MODULE_DIR_ . 'blockuserinfo/blockuserinfo.php')) {
                                    include_once(_PS_MODULE_DIR_ . 'blockuserinfo/blockuserinfo.php');
                                    $blockUserInfo = new BlockUserInfo();
                                }
                                $this->context->smarty->assign('isVirtualCart', $this->context->cart->isVirtualCart());
                                $this->_processAddressFormat();
                                $this->_assignAddress();
                                if (!($formatedAddressFieldsValuesList = $this->context->smarty->getTemplateVars('formatedAddressFieldsValuesList')))
                                    $formatedAddressFieldsValuesList = array();

                                // Wrapping fees
                                $wrapping_fees = $this->context->cart->getGiftWrappingPrice(false);
                                $wrapping_fees_tax_inc = $this->context->cart->getGiftWrappingPrice();
                                $return = array_merge(array(
                                    'order_opc_adress' => $this->context->smarty->fetch(_PS_THEME_DIR_ . 'order-address.tpl'),
                                    'block_user_info' => (isset($blockUserInfo) ? $blockUserInfo->hookDisplayTop(array()) : ''),
                                    'formatedAddressFieldsValuesList' => $formatedAddressFieldsValuesList,
                                    'carrier_data' => $this->_getCarrierList(),
                                    'HOOK_TOP_PAYMENT' => Hook::exec('displayPaymentTop'),
                                    'HOOK_PAYMENT' => $this->_getPaymentMethods(),
                                    'no_address' => 0,
                                    'gift_price' => Tools::displayPrice(Tools::convertPrice(Product::getTaxCalculationMethod() == 1 ? $wrapping_fees : $wrapping_fees_tax_inc, new Currency((int)$this->context->cookie->id_currency)))
                                ),
                                    $this->getFormatedSummaryDetail()
                                );
                                die(Tools::jsonEncode($return));
                            }
                            die(Tools::displayError());
                            break;

                        case 'makeFreeOrder':
                            /* Bypass payment step if total is 0 */
                            if (($id_order = $this->_checkFreeOrder()) && $id_order) {
                                $order = new Order((int)$id_order);
                                $email = $this->context->customer->email;
                                if ($this->context->customer->is_guest)
                                    $this->context->customer->logout(); // If guest we clear the cookie for security reason
                                die('freeorder:' . $order->reference . ':' . $email);
                            }
                            exit;
                            break;

                        case 'updateAddressesSelected':
                            if ($this->context->customer->isLogged(true)) {
                                $address_delivery = new Address((int)Tools::getValue('id_address_delivery'));
                                $this->context->smarty->assign('isVirtualCart', $this->context->cart->isVirtualCart());
                                $address_invoice = ((int)Tools::getValue('id_address_delivery') == (int)Tools::getValue('id_address_invoice') ? $address_delivery : new Address((int)Tools::getValue('id_address_invoice')));
                                if ($address_delivery->id_customer != $this->context->customer->id || $address_invoice->id_customer != $this->context->customer->id)
                                    $this->errors[] = Tools::displayError('This address is not yours.');
                                elseif (!Address::isCountryActiveById((int)Tools::getValue('id_address_delivery')))
                                    $this->errors[] = Tools::displayError('This address is not in a valid area.');
                                elseif (!Validate::isLoadedObject($address_delivery) || !Validate::isLoadedObject($address_invoice) || $address_invoice->deleted || $address_delivery->deleted)
                                    $this->errors[] = Tools::displayError('This address is invalid.');
                                else {
                                    $this->context->cart->id_address_delivery = (int)Tools::getValue('id_address_delivery');
                                    $this->context->cart->id_address_invoice = Tools::isSubmit('same') ? $this->context->cart->id_address_delivery : (int)Tools::getValue('id_address_invoice');
                                    if (!$this->context->cart->update())
                                        $this->errors[] = Tools::displayError('An error occurred while updating your cart.');

                                    $infos = Address::getCountryAndState((int)$this->context->cart->id_address_delivery);
                                    if (isset($infos['id_country']) && $infos['id_country']) {
                                        $country = new Country((int)$infos['id_country']);
                                        $this->context->country = $country;
                                    }
                                    // check qty


                                    // Address has changed, so we check if the cart rules still apply
                                    $cart_rules = $this->context->cart->getCartRules();
                                    CartRule::autoRemoveFromCart($this->context);
                                    CartRule::autoAddToCart($this->context);
                                    if ((int)Tools::getValue('allow_refresh')) {
                                        // If the cart rules has changed, we need to refresh the whole cart
                                        $cart_rules2 = $this->context->cart->getCartRules();
                                        if (count($cart_rules2) != count($cart_rules))
                                            $this->ajax_refresh = true;
                                        else {
                                            $rule_list = array();
                                            foreach ($cart_rules2 as $rule)
                                                $rule_list[] = $rule['id_cart_rule'];
                                            foreach ($cart_rules as $rule)
                                                if (!in_array($rule['id_cart_rule'], $rule_list)) {
                                                    $this->ajax_refresh = true;
                                                    break;
                                                }
                                        }
                                    }

                                    if (!$this->context->cart->isMultiAddressDelivery())
                                        $this->context->cart->setNoMultishipping(); // As the cart is no multishipping, set each delivery address lines with the main delivery address

                                    if (!count($this->errors)) {
                                        $result = $this->_getCarrierList();
                                        // Wrapping fees
                                        $wrapping_fees = $this->context->cart->getGiftWrappingPrice(false);
                                        $wrapping_fees_tax_inc = $this->context->cart->getGiftWrappingPrice();
                                        $result = array_merge($result, array(
                                            'HOOK_TOP_PAYMENT' => Hook::exec('displayPaymentTop'),
                                            'HOOK_PAYMENT' => $this->_getPaymentMethods(),
                                            'gift_price' => Tools::displayPrice(Tools::convertPrice(Product::getTaxCalculationMethod() == 1 ? $wrapping_fees : $wrapping_fees_tax_inc, new Currency((int)($this->context->cookie->id_currency)))),
                                            'carrier_data' => $this->_getCarrierList(),
                                            'refresh' => (bool)$this->ajax_refresh),
                                            $this->getFormatedSummaryDetail()
                                        );
                                        die(Tools::jsonEncode($result));
                                    }
                                }
                                if (count($this->errors))
                                    die(Tools::jsonEncode(array(
                                        'hasError' => true,
                                        'errors' => $this->errors
                                    )));
                            }
                            die(Tools::displayError());
                            break;

                        case 'multishipping':
                            $this->_assignSummaryInformations();
                            $this->context->smarty->assign('product_list', $this->context->cart->getProducts());

                            if ($this->context->customer->id)
                                $this->context->smarty->assign('address_list', $this->context->customer->getAddresses($this->context->language->id));
                            else
                                $this->context->smarty->assign('address_list', array());
                            $this->setTemplate(_PS_THEME_DIR_ . 'order-address-multishipping-products.tpl');
                            $this->display();
                            die();
                            break;

                        case 'checkQuantityAvailable':
                            $this->_assignSummaryInformations();
                            $cart = $this->context->cart;
                            $products = $cart->getProducts();
                            $changed_product = [];
                            $status = false;

                            foreach ($products as $product) {
                                if ((int)$product['cart_quantity'] > (int)$product['quantity_available'] && (int)$product['quantity_available'] != 0) {
                                    $subtract = (int)$product['cart_quantity'] - (int)$product['quantity_available'];
                                    $cart->updateQty($subtract, $product['id_product'], $product['id_product_attribute'], false, 'down');

                                    $status = true;

                                    $changed_product[] = [
                                        'id_product' => $product['id_product'],
                                        'quantity' => $product['quantity_available'],
                                        'name' => $product['name'] . ($product['attributes'] ? ', ' . $product['attributes'] : ''),
                                    ];
                                }
                            }

                            if (count($changed_product)) {
                                $this->context->smarty->assign($this->context->cart->getSummaryDetails());
                                $this->context->smarty->assign('opc', true);

                                $template = $this->context->smarty->fetch(_PS_THEME_DIR_ . 'shopping-cart.tpl');

                                $changed_products_text = '';

                                foreach ($changed_product as $product) {
                                    $changed_products_text .= sprintf("%s - %d шт.<br>", $product['name'], $product['quantity']);
                                }

                                die(Tools::jsonEncode(array(
                                    'status' => $status,
                                    'notification' => 'Ввиду недостатка товара на складе количества товаров в вашей корзине были изменены',
                                    'additional_text' => $changed_products_text,
                                    'template' => $template,
                                )));
                            }

                            die(Tools::jsonEncode(array(
                                'status' => $status
                            )));
                            break;

                        case 'cartReload':
                            $this->_assignSummaryInformations();
                            if ($this->context->customer->id)
                                $this->context->smarty->assign('address_list', $this->context->customer->getAddresses($this->context->language->id));
                            else
                                $this->context->smarty->assign('address_list', array());
                            $this->context->smarty->assign('opc', true);
                            $this->setTemplate(_PS_THEME_DIR_ . 'shopping-cart.tpl');
                            $this->display();
                            die();
                            break;

                        case 'noMultiAddressDelivery':
                            $this->context->cart->setNoMultishipping();
                            die();
                            break;

                        case 'updateCartDiscounts':
                            CartRule::cleanCache();
                            $cart_rules = $this->context->cart->getCartRules();
                            foreach ($cart_rules as $cart_rule)
                                if ((int)$cart_rule['obj']->id != (int)Tools::getValue('cart_rule_id'))
                                    $this->context->cart->removeCartRule((int)$cart_rule['obj']->id);
                            die();
                            break;

                        case 'requestDeliveryCost':
                            $postcode = Tools::getValue('postcode', null);
                            $delivery_option = Tools::getValue('delivery_option', null);

                            // If not Russian post
                            if ($delivery_option != '22,') {
                                die(Tools::jsonEncode(['error' => 'not russian post']));
                            }

                            if (!$postcode) {
                                die(Tools::jsonEncode(['error' => 'empty postcode']));
                            }

                            $total_weight = $this->context->cart->getTotalWeight();
                            $order_total_price = $this->context->cart->getOrderTotal(false);

                            $calculation_helper = new HelperRussianPostDelivery();
                            $response = $calculation_helper->get_calculation($postcode, $total_weight, $order_total_price);

                            if (isset($response['error'])) {
                                die(Tools::jsonEncode($response));
                            } else {
                                // Success
                                $this->context->cart->setCalculatedDeliveryCost($response['delivery_cost']);
                                $response['order_total_with_shipping'] = $order_total_price + $response['delivery_cost'];
                                die(Tools::jsonEncode($response));
                            }
                            break;

                        default:
                            throw new PrestaShopException('Unknown method "' . Tools::getValue('method') . '"');
                    }
                } else
                    throw new PrestaShopException('Method is not defined');
            }
        } elseif (Tools::isSubmit('ajax')) {
            $this->errors[] = Tools::displayError('No product in your cart.');
            die('{"hasError" : true, "errors" : ["' . implode('\',\'', $this->errors) . '"]}');
        }
    }


    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        FrontController::initContent();

        /* id_carrier is not defined in database before choosing a carrier, set it to a default one to match a potential cart _rule */
        if (empty($this->context->cart->id_carrier)) {
            $checked = $this->context->cart->simulateCarrierSelectedOutput();
            $checked = ((int)Cart::desintifier($checked));
            $this->context->cart->id_carrier = $checked;
            $this->context->cart->update();
        }

        CartRule::autoRemoveFromCart($this->context);
        CartRule::autoAddToCart($this->context);

        // SHOPPING CART
        $this->_assignSummaryInformations();
        // WRAPPING AND TOS
        $this->_assignWrappingAndTOS();

        if (Configuration::get('PS_RESTRICT_DELIVERED_COUNTRIES'))
            $countries = Carrier::getDeliveredCountries($this->context->language->id, true, true);
        else
            $countries = Country::getCountries($this->context->language->id, true);

        // If a rule offer free-shipping, force hidding shipping prices
        $free_shipping = false;
        foreach ($this->context->cart->getCartRules() as $rule)
            if ($rule['free_shipping'] && !$rule['carrier_restriction']) {
                $free_shipping = true;
                break;
            }

        $this->context->smarty->assign(array(
            'free_shipping' => $free_shipping,
            'isGuest' => isset($this->context->cookie->is_guest) ? $this->context->cookie->is_guest : 0,
            'countries' => $countries,
            'sl_country' => (int)Tools::getCountry(),
            'PS_GUEST_CHECKOUT_ENABLED' => Configuration::get('PS_GUEST_CHECKOUT_ENABLED'),
            'errorCarrier' => Tools::displayError('You must choose a carrier.', false),
            'errorTOS' => Tools::displayError('You must accept the Terms of Service.', false),
            'isPaymentStep' => (bool)(isset($_GET['isPaymentStep']) && $_GET['isPaymentStep']),
            'genders' => Gender::getGenders(),
            'one_phone_at_least' => (int)Configuration::get('PS_ONE_PHONE_AT_LEAST'),
            'HOOK_CREATE_ACCOUNT_FORM' => Hook::exec('displayCustomerAccountForm'),
            'HOOK_CREATE_ACCOUNT_TOP' => Hook::exec('displayCustomerAccountFormTop'),
            'point_of_delivery' => Store::getPointsOfDelivery()[0],
            'total_weight' => $this->context->cart->getTotalWeight(),
        ));
        $years = Tools::dateYears();
        $months = Tools::dateMonths();
        $days = Tools::dateDays();
        $this->context->smarty->assign(array(
            'years' => $years,
            'months' => $months,
            'days' => $days,
        ));

        /* Load guest informations */
        if ($this->isLogged && $this->context->cookie->is_guest)
            $this->context->smarty->assign('guestInformations', $this->_getGuestInformations());
        // ADDRESS
        if ($this->isLogged)
            $this->context->smarty->assign('guestInformations', $this->_getGuestInformations());
        // $this->_assignAddress();

        // CARRIER
        $this->_assignCarrier();
        // PAYMENT
        $this->_assignPayment();
        Tools::safePostVars();

        $blocknewsletter = Module::getInstanceByName('blocknewsletter');
        $this->context->smarty->assign('newsletter', (bool)$blocknewsletter && $blocknewsletter->active);
        $this->context->smarty->assign('field_required', $this->context->customer->validateFieldsRequiredDatabase());

        $this->_processAddressFormat();

        $this->setTemplate(_PS_THEME_DIR_ . 'order-opc.tpl');
    }


    protected function _getGuestInformations()
    {
        $customer = $this->context->customer;
        $address_delivery = new Address($this->context->cart->id_address_delivery);

        if ($customer->birthday)
            $birthday = explode('-', $customer->birthday);
        else
            $birthday = array('0', '0', '0');

        return array(
            'id_customer' => (int)$customer->id,
            'email' => Tools::htmlentitiesUTF8($customer->email),
            'customer_lastname' => Tools::htmlentitiesUTF8($customer->lastname),
            'customer_firstname' => Tools::htmlentitiesUTF8($customer->firstname),
            'newsletter' => (int)$customer->newsletter,
            'optin' => (int)$customer->optin,
            'id_address_delivery' => (int)$this->context->cart->id_address_delivery,
            'lastname' => Tools::htmlentitiesUTF8($customer->lastname),
            'firstname' => Tools::htmlentitiesUTF8($customer->firstname),
            'address1' => Tools::htmlentitiesUTF8($address_delivery->address1),
            'region' => Tools::htmlentitiesUTF8($address_delivery->address2),
            'postcode' => Tools::htmlentitiesUTF8($address_delivery->postcode),
            'city' => Tools::htmlentitiesUTF8($address_delivery->city),
            'phone' => Tools::htmlentitiesUTF8($address_delivery->phone),
            'id_country' => (int)$address_delivery->id_country,
            'sl_year' => $birthday[0],
            'sl_month' => $birthday[1],
            'sl_day' => $birthday[2],
            'discount' => $customer->getDiscountCard(),
        );
    }


    protected function _getPaymentMethods()
    {
        # Переместить в валидатор заказа!

        // if (!$this->isLogged)
        //     return '<p class="warning">'.Tools::displayError('Please sign in to see payment methods.').'</p>';
        // if ($this->context->cart->OrderExists())
        //     return '<p class="warning">'.Tools::displayError('Error: This order has already been validated.').'</p>';
        // if (!$this->context->cart->id_customer || !Customer::customerIdExistsStatic($this->context->cart->id_customer) || Customer::isBanned($this->context->cart->id_customer))
        //     return '<p class="warning">'.Tools::displayError('Error: No customer.').'</p>';
        // $address_delivery = new Address($this->context->cart->id_address_delivery);
        // $address_invoice = ($this->context->cart->id_address_delivery == $this->context->cart->id_address_invoice ? $address_delivery : new Address($this->context->cart->id_address_invoice));
        // if (!$this->context->cart->id_address_delivery || !$this->context->cart->id_address_invoice || !Validate::isLoadedObject($address_delivery) || !Validate::isLoadedObject($address_invoice) || $address_invoice->deleted || $address_delivery->deleted)
        //     return '<p class="warning">'.Tools::displayError('Error: Please select an address.').'</p>';
        // if (count($this->context->cart->getDeliveryOptionList()) == 0 && !$this->context->cart->isVirtualCart())
        // {
        //     if ($this->context->cart->isMultiAddressDelivery())
        //         return '<p class="warning">'.Tools::displayError('Error: None of your chosen carriers deliver to some of  the addresses you\'ve selected.').'</p>';
        //     else
        //         return '<p class="warning">'.Tools::displayError('Error: None of your chosen carriers deliver to the address you\'ve selected.').'</p>';
        // }
        // if (!$this->context->cart->getDeliveryOption(null, false) && !$this->context->cart->isVirtualCart())
        //     return '<p class="warning">'.Tools::displayError('Error: Please choose a carrier.').'</p>';
        // if (!$this->context->cart->id_currency)
        //     return '<p class="warning">'.Tools::displayError('Error: No currency has been selected.').'</p>';
        // if (!$this->context->cookie->checkedTOS && Configuration::get('PS_CONDITIONS'))
        //  return '<p class="warning">'.Tools::displayError('Please accept the Terms of Service.').'</p>';

        /* If some products have disappear */
        // if (!$this->context->cart->checkQuantities())
        //     return '<p class="warning">'.Tools::displayError('An item in your cart is no longer available. You cannot proceed with your order.').'</p>';

        /* Check minimal amount */
        // $currency = Currency::getCurrency((int)$this->context->cart->id_currency);

        // $minimal_purchase = Tools::convertPrice((float)Configuration::get('PS_PURCHASE_MINIMUM'), $currency);
        // if ($this->context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS) < $minimal_purchase)
        //     return '<p class="warning">'.sprintf(
        //         Tools::displayError('A minimum purchase total of %1s (tax excl.) is required in order to validate your order, current purchase total is %2s (tax excl.).'),
        //         Tools::displayPrice($minimal_purchase, $currency), Tools::displayPrice($this->context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS), $currency)
        //     ).'</p>';

        /* Bypass payment step if total is 0 */
        // if ($this->context->cart->getOrderTotal() <= 0)
        //     return '<p class="center"><button class="button btn btn-default button-medium" name="confirmOrder" id="confirmOrder" onclick="confirmFreeOrder();" type="submit"> <span>'.Tools::displayError('I confirm my order.').'</span></button></p>';

        // if (Tools::isSubmit('ajax'))
        //     d('get payments depend on delivery');

        $return = Hook::exec('displayPayment');
        if (!$return)
            return '<p class="warning">' . Tools::displayError('No payment method is available for use at this time. ') . '</p>';
        return $return;
    }
}
