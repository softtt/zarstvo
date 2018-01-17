<?php

/**
 * Module is prohibited to sales! Violation of this condition leads to the deprivation of the license!
 *
 * @category  Front Office Features
 * @package   Yandex Payment Solution
 * @author    Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015 NBCO Yandex.Money LLC
 * @license   https://money.yandex.ru/doc.xml?id=527052
 */
class AdminOrdersController extends AdminOrdersControllerCore
{


    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'order';
        $this->className = 'Order';
        $this->lang = false;
        $this->addRowAction('view');
        $this->explicitSelect = true;
        $this->allow_export = true;
        $this->deleted = false;
        $this->context = Context::getContext();

        $this->_select = '
		a.id_currency,
		a.id_order AS id_pdf,
		CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`,
		osl.`name` AS `osname`,
		os.`color`,
		IF((SELECT so.id_order FROM `' . _DB_PREFIX_ . 'orders` so WHERE so.id_customer = a.id_customer AND so.id_order < a.id_order LIMIT 1) > 0, 0, 1) as new,
		country_lang.name as cname,
		IF(a.valid, 1, 0) badge_success';

        $this->_join = '
		LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.`id_customer` = a.`id_customer`)
		INNER JOIN `' . _DB_PREFIX_ . 'address` address ON address.id_address = a.id_address_delivery
		INNER JOIN `' . _DB_PREFIX_ . 'country` country ON address.id_country = country.id_country
		INNER JOIN `' . _DB_PREFIX_ . 'country_lang` country_lang ON (country.`id_country` = country_lang.`id_country` AND country_lang.`id_lang` = ' . (int)$this->context->language->id . ')
		LEFT JOIN `' . _DB_PREFIX_ . 'order_state` os ON (os.`id_order_state` = a.`current_state`)
		LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = ' . (int)$this->context->language->id . ')';
        $this->_orderBy = 'id_order';
        $this->_orderWay = 'DESC';

        $statuses = OrderState::getOrderStates((int)$this->context->language->id);
        foreach ($statuses as $status)
            $this->statuses_array[$status['id_order_state']] = $status['name'];

        $this->fields_list = array(
            'id_order' => array(
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ),
            'reference' => array(
                'title' => $this->l('Reference')
            ),
            'customer' => array(
                'title' => $this->l('Customer'),
                'havingFilter' => true,
            ),
        );

        if (Configuration::get('PS_B2B_ENABLE')) {
            $this->fields_list = array_merge($this->fields_list, array(
                'company' => array(
                    'title' => $this->l('Company'),
                    'filter_key' => 'c!company'
                ),
            ));
        }

        $this->fields_list = array_merge($this->fields_list, array(
            'total_paid_tax_incl' => array(
                'title' => $this->l('Total'),
                'align' => 'text-right',
                'type' => 'price',
                'currency' => true,
                'callback' => 'setOrderCurrency',
                'badge_success' => true
            ),
            'shipping_number' => array(
                'title' => $this->l('Код доставки'),
                'align' => 'text-right',
                'type' => 'select',
                'specialInput' => true,
                'class' => 'state'
            ),
            'payment' => array(
                'title' => $this->l('Payment')
            ),
            'osname' => array(
                'title' => $this->l('Status'),
                'statusSelector' => 'state',
                'type' => 'select',
                'class' => 'state',
                'list' => $this->statuses_array,
                'filter_key' => 'os!id_order_state',
                'filter_type' => 'int',
                'order_key' => 'osname'
            ),
            'date_add' => array(
                'title' => $this->l('Date'),
                'align' => 'text-right',
                'type' => 'datetime',
                'filter_key' => 'a!date_add'
            ),
            'id_pdf' => array(
                'title' => $this->l('PDF'),
                'align' => 'text-center',
                'callback' => 'printPDFIcons',
                'orderby' => false,
                'search' => false,
                'remove_onclick' => true
            )
        ));


        $this->shopLinkType = 'shop';
        $this->shopShareDatas = Shop::SHARE_ORDER;

        if (Tools::isSubmit('id_order')) {
            // Save context (in order to apply cart rule)
            $order = new Order((int)Tools::getValue('id_order'));
            $this->context->cart = new Cart($order->id_cart);
            $this->context->customer = new Customer($order->id_customer);
        }

        $this->bulk_actions = array(
            'updateOrderStatus' => array('text' => $this->l('Change Order Status'), 'icon' => 'icon-refresh')
        );

        AdminControllerCore::__construct();
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addJS(_PS_JS_DIR_ . 'orders.js');

    }
    public function displaySpecialInput($tpl, $token, $id)
    {
        $order = new Order($id);
        $tpl->assign(array(
            'orderCarrier' => $order->id_carrier,
            'order' => $order,
            'currentIndex' => self::$currentIndex,
            'currentCode' => $order->shipping_number,
            'id' => $id
        ));
        return $tpl->fetch();
    }
    public function displayStatusSelector($tpl, $token, $id, $statuses_list, $id_category = null, $id_product = null)
    {
        $tpl_status_selector = $tpl;
        $order = new Order($id);

        $tpl_status_selector->assign(array(
            //'ajax' => $ajax,
            'currentIndex' => self::$currentIndex,
            'currentState' => $order->getCurrentOrderState(),
            'id' => $id,
            'states' => OrderState::getOrderStates($this->context->language->id),
            //'enabled' => $active,
            'list' => $statuses_list,
        ));
        return $tpl_status_selector->fetch();
    }

    /*
    * module: yamodule
    * date: 2016-10-19 14:41:59
    * version: 1.3.9
    */
    public function displayReturnsLink($token, $id)
    {
        return '<a href="' . $this->context->link->getAdminLink('AdminOrders') . '&token=' . $token
        . '&id_order=' . $id . '&viewReturns"><i class="icon-gift"></i> Возвраты</a>';
    }


    /*
    * module: yamodule
    * date: 2016-10-19 14:41:59
    * version: 1.3.9
    */
    public function renderList()
    {
        if (Tools::isSubmit('viewReturns')) {
            $id_order = Tools::getValue('id_order', 0);
            if ($id_order) {
                $module = new Yamodule();
                $params = array('order' => new Order($id_order));
                $this->content .= $module->displayReturnsContentTabs($params);
                $this->content .= $module->displayReturnsContent($params);
            } else {
                $this->errors[] = $this->l('There is no order number!');
            }
        } else {
            return parent::renderList();
        }
    }

    public function postProcess()
    {
        // If id_order is sent, we instanciate a new Order object
        if (Tools::isSubmit('id_order') && Tools::getValue('id_order') > 0) {
            $order = new Order(Tools::getValue('id_order'));
            if (!Validate::isLoadedObject($order))
                $this->errors[] = Tools::displayError('The order cannot be found within your database.');
            ShopUrl::cacheMainDomainForShop((int)$order->id_shop);
        }

        /* Update shipping number */
        if (Tools::isSubmit('submitShippingNumber') && isset($order)) {
            if ($this->tabAccess['edit'] === '1') {
                $order_carrier = new OrderCarrier(Tools::getValue('id_order_carrier'));
                if (!Validate::isLoadedObject($order_carrier))
                    $this->errors[] = Tools::displayError('The order carrier ID is invalid.');
                elseif (!Validate::isTrackingNumber(Tools::getValue('tracking_number')))
                    $this->errors[] = Tools::displayError('The tracking number is incorrect.');
                else {
                    // update shipping number
                    // Keep these two following lines for backward compatibility, remove on 1.6 version
                    $order->shipping_number = Tools::getValue('tracking_number');
                    $order->update();

                    // Update order_carrier
                    $order_carrier->tracking_number = pSQL(Tools::getValue('tracking_number'));
                    if ($order_carrier->update()) {
                        // Send mail to customer
                        $customer = new Customer((int)$order->id_customer);
                        $carrier = new Carrier((int)$order->id_carrier, $order->id_lang);
                        if (!Validate::isLoadedObject($customer))
                            throw new PrestaShopException('Can\'t load Customer object');
                        if (!Validate::isLoadedObject($carrier))
                            throw new PrestaShopException('Can\'t load Carrier object');
                        $templateVars = array(
                            '{followup}' => str_replace('@', $order->shipping_number, $carrier->url),
                            '{firstname}' => $customer->firstname,
                            '{lastname}' => $customer->lastname,
                            '{id_order}' => $order->id,
                            '{shipping_number}' => $order->shipping_number,
                            '{order_name}' => $order->id
                        );
                        if (@Mail::Send((int)$order->id_lang, 'in_transit', Mail::l('Package in transit', (int)$order->id_lang), $templateVars,
                            $customer->email, $customer->firstname . ' ' . $customer->lastname, null, null, null, null,
                            _PS_MAIL_DIR_, true, (int)$order->id_shop)
                        ) {
                            Hook::exec('actionAdminOrdersTrackingNumberUpdate', array('order' => $order, 'customer' => $customer, 'carrier' => $carrier), null, false, true, false, $order->id_shop);

                            if (Tools::getValue('quick'))
                                Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders'));

                            Tools::redirectAdmin(self::$currentIndex . '&id_order=' . $order->id . '&vieworder&conf=4&token=' . $this->token);
                        } else
                            $this->errors[] = Tools::displayError('An error occurred while sending an email to the customer.');
                    } else
                        $this->errors[] = Tools::displayError('The order carrier cannot be updated.');
                }
            } else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        } /* Change order status, add a new entry in order history and send an e-mail to the customer if needed */
        elseif (Tools::isSubmit('submitState') && isset($order)) {
            if ($this->tabAccess['edit'] === '1') {
                $order_state = new OrderState(Tools::getValue('id_order_state'));

                if (!Validate::isLoadedObject($order_state))
                    $this->errors[] = Tools::displayError('The new order status is invalid.');
                else {
                    $current_order_state = $order->getCurrentOrderState();
                    if ($current_order_state->id != $order_state->id) {
                        // Create new OrderHistory
                        $history = new OrderHistory();
                        $history->id_order = $order->id;
                        $history->id_employee = (int)$this->context->employee->id;

                        $use_existings_payment = false;
                        if (!$order->hasInvoice())
                            $use_existings_payment = true;
                        $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);

                        $carrier = new Carrier($order->id_carrier, $order->id_lang);
                        $templateVars = array();

                        if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                            $templateVars['{followup}'] = str_replace('@', $order->shipping_number, $carrier->url);
                        }

                        if ($history->id_order_state == Configuration::get('PS_OS_SBERBANK')) {
                            $templateVars['{sberbankpayment_details}'] = Configuration::get('SBER_BANK_DETAILS');
                        }

                        // Save all changes
                        if ($history->addWithemail(true, $templateVars)) {
                            // synchronizes quantities if needed..
                            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                                foreach ($order->getProducts() as $product) {
                                    if (StockAvailable::dependsOnStock($product['product_id']))
                                        StockAvailable::synchronize($product['product_id'], (int)$product['id_shop']);
                                }
                            }
                            if (Tools::getValue('quick'))
                                Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders'));

                            Tools::redirectAdmin(self::$currentIndex . '&id_order=' . (int)$order->id . '&vieworder&token=' . $this->token);
                        }
                        $this->errors[] = Tools::displayError('An error occurred while changing order status, or we were unable to send an email to the customer.');
                    } else
                        $this->errors[] = Tools::displayError('The order has already been assigned this status.');
                }
            } else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        } /* Add a new message for the current order and send an e-mail to the customer if needed */
        elseif (Tools::isSubmit('submitMessage') && isset($order)) {
            if ($this->tabAccess['edit'] === '1') {
                $customer = new Customer(Tools::getValue('id_customer'));
                if (!Validate::isLoadedObject($customer))
                    $this->errors[] = Tools::displayError('The customer is invalid.');
                elseif (!Tools::getValue('message'))
                    $this->errors[] = Tools::displayError('The message cannot be blank.');
                else {
                    /* Get message rules and and check fields validity */
                    $rules = call_user_func(array('Message', 'getValidationRules'), 'Message');
                    foreach ($rules['required'] as $field)
                        if (($value = Tools::getValue($field)) == false && (string)$value != '0')
                            if (!Tools::getValue('id_' . $this->table) || $field != 'passwd')
                                $this->errors[] = sprintf(Tools::displayError('field %s is required.'), $field);
                    foreach ($rules['size'] as $field => $maxLength)
                        if (Tools::getValue($field) && Tools::strlen(Tools::getValue($field)) > $maxLength)
                            $this->errors[] = sprintf(Tools::displayError('field %1$s is too long (%2$d chars max).'), $field, $maxLength);
                    foreach ($rules['validate'] as $field => $function)
                        if (Tools::getValue($field))
                            if (!Validate::$function(htmlentities(Tools::getValue($field), ENT_COMPAT, 'UTF-8')))
                                $this->errors[] = sprintf(Tools::displayError('field %s is invalid.'), $field);

                    if (!count($this->errors)) {
                        //check if a thread already exist
                        $id_customer_thread = CustomerThread::getIdCustomerThreadByEmailAndIdOrder($customer->email, $order->id);
                        if (!$id_customer_thread) {
                            $customer_thread = new CustomerThread();
                            $customer_thread->id_contact = 0;
                            $customer_thread->id_customer = (int)$order->id_customer;
                            $customer_thread->id_shop = (int)$this->context->shop->id;
                            $customer_thread->id_order = (int)$order->id;
                            $customer_thread->id_lang = (int)$this->context->language->id;
                            $customer_thread->email = $customer->email;
                            $customer_thread->status = 'open';
                            $customer_thread->token = Tools::passwdGen(12);
                            $customer_thread->add();
                        } else
                            $customer_thread = new CustomerThread((int)$id_customer_thread);

                        $customer_message = new CustomerMessage();
                        $customer_message->id_customer_thread = $customer_thread->id;
                        $customer_message->id_employee = (int)$this->context->employee->id;
                        $customer_message->message = Tools::getValue('message');
                        $customer_message->private = Tools::getValue('visibility');

                        if (!$customer_message->add())
                            $this->errors[] = Tools::displayError('An error occurred while saving the message.');
                        elseif ($customer_message->private)
                            Tools::redirectAdmin(self::$currentIndex . '&id_order=' . (int)$order->id . '&vieworder&conf=11&token=' . $this->token);
                        else {
                            $message = $customer_message->message;
                            if (Configuration::get('PS_MAIL_TYPE', null, null, $order->id_shop) != Mail::TYPE_TEXT)
                                $message = Tools::nl2br($customer_message->message);

                            $varsTpl = array(
                                '{lastname}' => $customer->lastname,
                                '{firstname}' => $customer->firstname,
                                '{id_order}' => $order->id,
                                '{order_name}' => $order->id,
                                '{message}' => $message
                            );
                            if (@Mail::Send((int)$order->id_lang, 'order_merchant_comment',
                                Mail::l('New message regarding your order', (int)$order->id_lang), $varsTpl, $customer->email,
                                $customer->firstname . ' ' . $customer->lastname, null, null, null, null, _PS_MAIL_DIR_, true, (int)$order->id_shop)
                            )
                                Tools::redirectAdmin(self::$currentIndex . '&id_order=' . $order->id . '&vieworder&conf=11' . '&token=' . $this->token);
                        }
                        $this->errors[] = Tools::displayError('An error occurred while sending an email to the customer.');
                    }
                }
            } else
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
        } /* Partial refund from order */
        elseif (Tools::isSubmit('partialRefund') && isset($order)) {
            if ($this->tabAccess['edit'] == '1') {
                if (is_array($_POST['partialRefundProduct'])) {
                    $amount = 0;
                    $order_detail_list = array();
                    foreach ($_POST['partialRefundProduct'] as $id_order_detail => $amount_detail) {
                        $order_detail_list[$id_order_detail] = array(
                            'quantity' => (int)$_POST['partialRefundProductQuantity'][$id_order_detail],
                            'id_order_detail' => (int)$id_order_detail
                        );

                        $order_detail = new OrderDetail((int)$id_order_detail);
                        if (empty($amount_detail)) {
                            $order_detail_list[$id_order_detail]['unit_price'] = $order_detail->unit_price_tax_excl;
                            $order_detail_list[$id_order_detail]['amount'] = $order_detail->unit_price_tax_incl * $order_detail_list[$id_order_detail]['quantity'];
                        } else {
                            $order_detail_list[$id_order_detail]['unit_price'] = (float)str_replace(',', '.', $amount_detail / $order_detail_list[$id_order_detail]['quantity']);
                            $order_detail_list[$id_order_detail]['amount'] = (float)str_replace(',', '.', $amount_detail);
                        }
                        $amount += $order_detail_list[$id_order_detail]['amount'];
                        if (!$order->hasBeenDelivered() || ($order->hasBeenDelivered() && Tools::isSubmit('reinjectQuantities')) && $order_detail_list[$id_order_detail]['quantity'] > 0)
                            $this->reinjectQuantity($order_detail, $order_detail_list[$id_order_detail]['quantity']);
                    }

                    $choosen = false;
                    $voucher = 0;

                    if ((int)Tools::getValue('refund_voucher_off') == 1)
                        $amount -= $voucher = (float)Tools::getValue('order_discount_price');
                    elseif ((int)Tools::getValue('refund_voucher_off') == 2) {
                        $choosen = true;
                        $amount = $voucher = (float)Tools::getValue('refund_voucher_choose');
                    }

                    $shipping_cost_amount = (float)str_replace(',', '.', Tools::getValue('partialRefundShippingCost')) ? (float)str_replace(',', '.', Tools::getValue('partialRefundShippingCost')) : false;
                    if ($shipping_cost_amount > 0)
                        $amount += $shipping_cost_amount;

                    $order_carrier = new OrderCarrier((int)$order->getIdOrderCarrier());
                    if (Validate::isLoadedObject($order_carrier)) {
                        $order_carrier->weight = (float)$order->getTotalWeight();
                        if ($order_carrier->update())
                            $order->weight = sprintf("%.3f " . Configuration::get('PS_WEIGHT_UNIT'), $order_carrier->weight);
                    }

                    if ($amount > 0) {
                        if (!OrderSlip::create($order, $order_detail_list, $shipping_cost_amount, $voucher, $choosen))
                            $this->errors[] = Tools::displayError('You cannot generate a partial credit slip.');

                        // Generate voucher
                        if (Tools::isSubmit('generateDiscountRefund') && !count($this->errors)) {
                            $cart_rule = new CartRule();
                            $cart_rule->description = sprintf($this->l('Credit slip for order #%d'), $order->id);
                            $languages = Language::getLanguages(false);
                            foreach ($languages as $language)
                                // Define a temporary name
                                $cart_rule->name[$language['id_lang']] = sprintf('V0C%1$dO%2$d', $order->id_customer, $order->id);

                            // Define a temporary code
                            $cart_rule->code = sprintf('V0C%1$dO%2$d', $order->id_customer, $order->id);
                            $cart_rule->quantity = 1;
                            $cart_rule->quantity_per_user = 1;

                            // Specific to the customer
                            $cart_rule->id_customer = $order->id_customer;
                            $now = time();
                            $cart_rule->date_from = date('Y-m-d H:i:s', $now);
                            $cart_rule->date_to = date('Y-m-d H:i:s', $now + (3600 * 24 * 365.25)); /* 1 year */
                            $cart_rule->partial_use = 1;
                            $cart_rule->active = 1;

                            $cart_rule->reduction_amount = $amount;
                            $cart_rule->reduction_tax = true;
                            $cart_rule->minimum_amount_currency = $order->id_currency;
                            $cart_rule->reduction_currency = $order->id_currency;

                            if (!$cart_rule->add())
                                $this->errors[] = Tools::displayError('You cannot generate a voucher.');
                            else {
                                // Update the voucher code and name
                                foreach ($languages as $language)
                                    $cart_rule->name[$language['id_lang']] = sprintf('V%1$dC%2$dO%3$d', $cart_rule->id, $order->id_customer, $order->id);
                                $cart_rule->code = sprintf('V%1$dC%2$dO%3$d', $cart_rule->id, $order->id_customer, $order->id);

                                if (!$cart_rule->update())
                                    $this->errors[] = Tools::displayError('You cannot generate a voucher.');
                                else {
                                    $currency = $this->context->currency;
                                    $customer = new Customer((int)($order->id_customer));
                                    $params['{lastname}'] = $customer->lastname;
                                    $params['{firstname}'] = $customer->firstname;
                                    $params['{id_order}'] = $order->id;
                                    $params['{order_name}'] = $order->id;
                                    $params['{voucher_amount}'] = Tools::displayPrice($cart_rule->reduction_amount, $currency, false);
                                    $params['{voucher_num}'] = $cart_rule->code;
                                    $customer = new Customer((int)$order->id_customer);
                                    @Mail::Send((int)$order->id_lang, 'voucher', sprintf(Mail::l('New voucher for your order #%s', (int)$order->id_lang), $order->reference),
                                        $params, $customer->email, $customer->firstname . ' ' . $customer->lastname, null, null, null,
                                        null, _PS_MAIL_DIR_, true, (int)$order->id_shop);
                                }
                            }
                        }
                    } else
                        $this->errors[] = Tools::displayError('You have to enter an amount if you want to create a partial credit slip.');

                    // Redirect if no errors
                    if (!count($this->errors))
                        Tools::redirectAdmin(self::$currentIndex . '&id_order=' . $order->id . '&vieworder&conf=30&token=' . $this->token);
                } else
                    $this->errors[] = Tools::displayError('The partial refund data is incorrect.');
            } else
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
        } /* Cancel product from order */
        elseif (Tools::isSubmit('cancelProduct') && isset($order)) {
            if ($this->tabAccess['delete'] === '1') {
                if (!Tools::isSubmit('id_order_detail') && !Tools::isSubmit('id_customization'))
                    $this->errors[] = Tools::displayError('You must select a product.');
                elseif (!Tools::isSubmit('cancelQuantity') && !Tools::isSubmit('cancelCustomizationQuantity'))
                    $this->errors[] = Tools::displayError('You must enter a quantity.');
                else {
                    $productList = Tools::getValue('id_order_detail');
                    if ($productList)
                        $productList = array_map('intval', $productList);

                    $customizationList = Tools::getValue('id_customization');
                    if ($customizationList)
                        $customizationList = array_map('intval', $customizationList);

                    $qtyList = Tools::getValue('cancelQuantity');
                    if ($qtyList)
                        $qtyList = array_map('intval', $qtyList);

                    $customizationQtyList = Tools::getValue('cancelCustomizationQuantity');
                    if ($customizationQtyList)
                        $customizationQtyList = array_map('intval', $customizationQtyList);

                    $full_product_list = $productList;
                    $full_quantity_list = $qtyList;

                    if ($customizationList)
                        foreach ($customizationList as $key => $id_order_detail) {
                            $full_product_list[(int)$id_order_detail] = $id_order_detail;
                            if (isset($customizationQtyList[$key]))
                                $full_quantity_list[(int)$id_order_detail] += $customizationQtyList[$key];
                        }

                    if ($productList || $customizationList) {
                        if ($productList) {
                            $id_cart = Cart::getCartIdByOrderId($order->id);
                            $customization_quantities = Customization::countQuantityByCart($id_cart);

                            foreach ($productList as $key => $id_order_detail) {
                                $qtyCancelProduct = abs($qtyList[$key]);
                                if (!$qtyCancelProduct)
                                    $this->errors[] = Tools::displayError('No quantity has been selected for this product.');

                                $order_detail = new OrderDetail($id_order_detail);
                                $customization_quantity = 0;
                                if (array_key_exists($order_detail->product_id, $customization_quantities) && array_key_exists($order_detail->product_attribute_id, $customization_quantities[$order_detail->product_id]))
                                    $customization_quantity = (int)$customization_quantities[$order_detail->product_id][$order_detail->product_attribute_id];

                                if (($order_detail->product_quantity - $customization_quantity - $order_detail->product_quantity_refunded - $order_detail->product_quantity_return) < $qtyCancelProduct)
                                    $this->errors[] = Tools::displayError('An invalid quantity was selected for this product.');

                            }
                        }
                        if ($customizationList) {
                            $customization_quantities = Customization::retrieveQuantitiesFromIds(array_keys($customizationList));

                            foreach ($customizationList as $id_customization => $id_order_detail) {
                                $qtyCancelProduct = abs($customizationQtyList[$id_customization]);
                                $customization_quantity = $customization_quantities[$id_customization];

                                if (!$qtyCancelProduct)
                                    $this->errors[] = Tools::displayError('No quantity has been selected for this product.');

                                if ($qtyCancelProduct > ($customization_quantity['quantity'] - ($customization_quantity['quantity_refunded'] + $customization_quantity['quantity_returned'])))
                                    $this->errors[] = Tools::displayError('An invalid quantity was selected for this product.');
                            }
                        }

                        if (!count($this->errors) && $productList)
                            foreach ($productList as $key => $id_order_detail) {
                                $qty_cancel_product = abs($qtyList[$key]);
                                $order_detail = new OrderDetail((int)($id_order_detail));

                                if (!$order->hasBeenDelivered() || ($order->hasBeenDelivered() && Tools::isSubmit('reinjectQuantities')) && $qty_cancel_product > 0)
                                    $this->reinjectQuantity($order_detail, $qty_cancel_product);

                                // Delete product
                                $order_detail = new OrderDetail((int)$id_order_detail);
                                if (!$order->deleteProduct($order, $order_detail, $qty_cancel_product))
                                    $this->errors[] = Tools::displayError('An error occurred while attempting to delete the product.') . ' <span class="bold">' . $order_detail->product_name . '</span>';
                                // Update weight SUM
                                $order_carrier = new OrderCarrier((int)$order->getIdOrderCarrier());
                                if (Validate::isLoadedObject($order_carrier)) {
                                    $order_carrier->weight = (float)$order->getTotalWeight();
                                    if ($order_carrier->update())
                                        $order->weight = sprintf("%.3f " . Configuration::get('PS_WEIGHT_UNIT'), $order_carrier->weight);
                                }
                                Hook::exec('actionProductCancel', array('order' => $order, 'id_order_detail' => (int)$id_order_detail), null, false, true, false, $order->id_shop);
                            }
                        if (!count($this->errors) && $customizationList)
                            foreach ($customizationList as $id_customization => $id_order_detail) {
                                $order_detail = new OrderDetail((int)($id_order_detail));
                                $qtyCancelProduct = abs($customizationQtyList[$id_customization]);
                                if (!$order->deleteCustomization($id_customization, $qtyCancelProduct, $order_detail))
                                    $this->errors[] = Tools::displayError('An error occurred while attempting to delete product customization.') . ' ' . $id_customization;
                            }
                        // E-mail params
                        if ((Tools::isSubmit('generateCreditSlip') || Tools::isSubmit('generateDiscount')) && !count($this->errors)) {
                            $customer = new Customer((int)($order->id_customer));
                            $params['{lastname}'] = $customer->lastname;
                            $params['{firstname}'] = $customer->firstname;
                            $params['{id_order}'] = $order->id;
                            $params['{order_name}'] = $order->id;
                        }

                        // Generate credit slip
                        if (Tools::isSubmit('generateCreditSlip') && !count($this->errors)) {
                            $product_list = array();
                            $amount = $order_detail->unit_price_tax_incl * $full_quantity_list[$id_order_detail];

                            $choosen = false;
                            if ((int)Tools::getValue('refund_total_voucher_off') == 1)
                                $amount -= $voucher = (float)Tools::getValue('order_discount_price');
                            elseif ((int)Tools::getValue('refund_total_voucher_off') == 2) {
                                $choosen = true;
                                $amount = $voucher = (float)Tools::getValue('refund_total_voucher_choose');
                            }
                            foreach ($full_product_list as $id_order_detail) {
                                $order_detail = new OrderDetail((int)$id_order_detail);
                                $product_list[$id_order_detail] = array(
                                    'id_order_detail' => $id_order_detail,
                                    'quantity' => $full_quantity_list[$id_order_detail],
                                    'unit_price' => $order_detail->unit_price_tax_excl,
                                    'amount' => isset($amount) ? $amount : $order_detail->unit_price_tax_incl * $full_quantity_list[$id_order_detail],
                                );
                            }

                            $shipping = Tools::isSubmit('shippingBack') ? null : false;

                            if (!OrderSlip::create($order, $product_list, $shipping, $voucher, $choosen))
                                $this->errors[] = Tools::displayError('A credit slip cannot be generated. ');
                            else {
                                Hook::exec('actionOrderSlipAdd', array('order' => $order, 'productList' => $full_product_list, 'qtyList' => $full_quantity_list), null, false, true, false, $order->id_shop);
                                @Mail::Send(
                                    (int)$order->id_lang,
                                    'credit_slip',
                                    Mail::l('New credit slip regarding your order', (int)$order->id_lang),
                                    $params,
                                    $customer->email,
                                    $customer->firstname . ' ' . $customer->lastname,
                                    null,
                                    null,
                                    null,
                                    null,
                                    _PS_MAIL_DIR_,
                                    true,
                                    (int)$order->id_shop
                                );
                            }
                        }

                        // Generate voucher
                        if (Tools::isSubmit('generateDiscount') && !count($this->errors)) {
                            $cartrule = new CartRule();
                            $languages = Language::getLanguages($order);
                            $cartrule->description = sprintf($this->l('Credit card slip for order #%d'), $order->id);
                            foreach ($languages as $language) {
                                // Define a temporary name
                                $cartrule->name[$language['id_lang']] = 'V0C' . (int)($order->id_customer) . 'O' . (int)($order->id);
                            }
                            // Define a temporary code
                            $cartrule->code = 'V0C' . (int)($order->id_customer) . 'O' . (int)($order->id);

                            $cartrule->quantity = 1;
                            $cartrule->quantity_per_user = 1;
                            // Specific to the customer
                            $cartrule->id_customer = $order->id_customer;
                            $now = time();
                            $cartrule->date_from = date('Y-m-d H:i:s', $now);
                            $cartrule->date_to = date('Y-m-d H:i:s', $now + (3600 * 24 * 365.25)); /* 1 year */
                            $cartrule->active = 1;

                            $products = $order->getProducts(false, $full_product_list, $full_quantity_list);

                            $total = 0;
                            foreach ($products as $product)
                                $total += $product['unit_price_tax_incl'] * $product['product_quantity'];

                            if (Tools::isSubmit('shippingBack'))
                                $total += $order->total_shipping;

                            if ((int)Tools::getValue('refund_total_voucher_off') == 1)
                                $total -= (float)Tools::getValue('order_discount_price');
                            elseif ((int)Tools::getValue('refund_total_voucher_off') == 2)
                                $total = (float)Tools::getValue('refund_total_voucher_choose');

                            $cartrule->reduction_amount = $total;
                            $cartrule->reduction_tax = true;
                            $cartrule->minimum_amount_currency = $order->id_currency;
                            $cartrule->reduction_currency = $order->id_currency;

                            if (!$cartrule->add())
                                $this->errors[] = Tools::displayError('You cannot generate a voucher.');
                            else {
                                // Update the voucher code and name
                                foreach ($languages as $language)
                                    $cartrule->name[$language['id_lang']] = 'V' . (int)($cartrule->id) . 'C' . (int)($order->id_customer) . 'O' . $order->id;
                                $cartrule->code = 'V' . (int)($cartrule->id) . 'C' . (int)($order->id_customer) . 'O' . $order->id;
                                if (!$cartrule->update())
                                    $this->errors[] = Tools::displayError('You cannot generate a voucher.');
                                else {
                                    $currency = $this->context->currency;
                                    $params['{voucher_amount}'] = Tools::displayPrice($cartrule->reduction_amount, $currency, false);
                                    $params['{voucher_num}'] = $cartrule->code;
                                    @Mail::Send((int)$order->id_lang, 'voucher', sprintf(Mail::l('New voucher for your order #%s', (int)$order->id_lang), $order->reference),
                                        $params, $customer->email, $customer->firstname . ' ' . $customer->lastname, null, null, null,
                                        null, _PS_MAIL_DIR_, true, (int)$order->id_shop);
                                }
                            }
                        }
                    } else
                        $this->errors[] = Tools::displayError('No product or quantity has been selected.');

                    // Redirect if no errors
                    if (!count($this->errors))
                        Tools::redirectAdmin(self::$currentIndex . '&id_order=' . $order->id . '&vieworder&conf=31&token=' . $this->token);
                }
            } else
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
        } elseif (Tools::isSubmit('messageReaded'))
            Message::markAsReaded(Tools::getValue('messageReaded'), $this->context->employee->id);
        elseif (Tools::isSubmit('submitAddPayment') && isset($order)) {
            if ($this->tabAccess['edit'] === '1') {
                $amount = str_replace(',', '.', Tools::getValue('payment_amount'));
                $currency = new Currency(Tools::getValue('payment_currency'));
                $order_has_invoice = $order->hasInvoice();
                if ($order_has_invoice)
                    $order_invoice = new OrderInvoice(Tools::getValue('payment_invoice'));
                else
                    $order_invoice = null;

                if (!Validate::isLoadedObject($order))
                    $this->errors[] = Tools::displayError('The order cannot be found');
                elseif (!Validate::isNegativePrice($amount) || !(float)$amount)
                    $this->errors[] = Tools::displayError('The amount is invalid.');
                elseif (!Validate::isGenericName(Tools::getValue('payment_method')))
                    $this->errors[] = Tools::displayError('The selected payment method is invalid.');
                elseif (!Validate::isString(Tools::getValue('payment_transaction_id')))
                    $this->errors[] = Tools::displayError('The transaction ID is invalid.');
                elseif (!Validate::isLoadedObject($currency))
                    $this->errors[] = Tools::displayError('The selected currency is invalid.');
                elseif ($order_has_invoice && !Validate::isLoadedObject($order_invoice))
                    $this->errors[] = Tools::displayError('The invoice is invalid.');
                elseif (!Validate::isDate(Tools::getValue('payment_date')))
                    $this->errors[] = Tools::displayError('The date is invalid');
                else {
                    if (!$order->addOrderPayment($amount, Tools::getValue('payment_method'), Tools::getValue('payment_transaction_id'), $currency, Tools::getValue('payment_date'), $order_invoice))
                        $this->errors[] = Tools::displayError('An error occurred during payment.');
                    else
                        Tools::redirectAdmin(self::$currentIndex . '&id_order=' . $order->id . '&vieworder&conf=4&token=' . $this->token);
                }
            } else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        } elseif (Tools::isSubmit('submitEditNote')) {
            $note = Tools::getValue('note');
            $order_invoice = new OrderInvoice((int)Tools::getValue('id_order_invoice'));
            if (Validate::isLoadedObject($order_invoice) && Validate::isCleanHtml($note)) {
                if ($this->tabAccess['edit'] === '1') {
                    $order_invoice->note = $note;
                    if ($order_invoice->save())
                        Tools::redirectAdmin(self::$currentIndex . '&id_order=' . $order_invoice->id_order . '&vieworder&conf=4&token=' . $this->token);
                    else
                        $this->errors[] = Tools::displayError('The invoice note was not saved.');
                } else
                    $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            } else
                $this->errors[] = Tools::displayError('The invoice for edit note was unable to load. ');
        } elseif (Tools::isSubmit('submitAddOrder') && ($id_cart = Tools::getValue('id_cart')) &&
            ($module_name = Tools::getValue('payment_module_name')) &&
            ($id_order_state = Tools::getValue('id_order_state')) && Validate::isModuleName($module_name)
        ) {
            if ($this->tabAccess['edit'] === '1') {
                if (!Configuration::get('PS_CATALOG_MODE'))
                    $payment_module = Module::getInstanceByName($module_name);
                else
                    $payment_module = new BoOrder();

                $cart = new Cart((int)$id_cart);
                Context::getContext()->currency = new Currency((int)$cart->id_currency);
                Context::getContext()->customer = new Customer((int)$cart->id_customer);

                $bad_delivery = false;
                if (($bad_delivery = (bool)!Address::isCountryActiveById((int)$cart->id_address_delivery))
                    || !Address::isCountryActiveById((int)$cart->id_address_invoice)
                ) {
                    if ($bad_delivery)
                        $this->errors[] = Tools::displayError('This delivery address country is not active.');
                    else
                        $this->errors[] = Tools::displayError('This invoice address country is not active.');
                } else {
                    $employee = new Employee((int)Context::getContext()->cookie->id_employee);
                    $payment_module->validateOrder(
                        (int)$cart->id, (int)$id_order_state,
                        $cart->getOrderTotal(true, Cart::BOTH), $payment_module->displayName, $this->l('Manual order -- Employee:') . ' ' .
                        substr($employee->firstname, 0, 1) . '. ' . $employee->lastname, array(), null, false, $cart->secure_key
                    );
                    if ($payment_module->currentOrder)
                        Tools::redirectAdmin(self::$currentIndex . '&id_order=' . $payment_module->currentOrder . '&vieworder' . '&token=' . $this->token);
                }
            } else
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
        } elseif ((Tools::isSubmit('submitAddressShipping') || Tools::isSubmit('submitAddressInvoice')) && isset($order)) {
            if ($this->tabAccess['edit'] === '1') {
                $address = new Address(Tools::getValue('id_address'));
                if (Validate::isLoadedObject($address)) {
                    // Update the address on order
                    if (Tools::isSubmit('submitAddressShipping'))
                        $order->id_address_delivery = $address->id;
                    elseif (Tools::isSubmit('submitAddressInvoice'))
                        $order->id_address_invoice = $address->id;
                    $order->update();
                    Tools::redirectAdmin(self::$currentIndex . '&id_order=' . $order->id . '&vieworder&conf=4&token=' . $this->token);
                } else
                    $this->errors[] = Tools::displayError('This address can\'t be loaded');
            } else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        } elseif (Tools::isSubmit('submitChangeCurrency') && isset($order)) {
            if ($this->tabAccess['edit'] === '1') {
                if (Tools::getValue('new_currency') != $order->id_currency && !$order->valid) {
                    $old_currency = new Currency($order->id_currency);
                    $currency = new Currency(Tools::getValue('new_currency'));
                    if (!Validate::isLoadedObject($currency))
                        throw new PrestaShopException('Can\'t load Currency object');

                    // Update order detail amount
                    foreach ($order->getOrderDetailList() as $row) {
                        $order_detail = new OrderDetail($row['id_order_detail']);
                        $fields = array(
                            'ecotax',
                            'product_price',
                            'reduction_amount',
                            'total_shipping_price_tax_excl',
                            'total_shipping_price_tax_incl',
                            'total_price_tax_incl',
                            'total_price_tax_excl',
                            'product_quantity_discount',
                            'purchase_supplier_price',
                            'reduction_amount',
                            'reduction_amount_tax_incl',
                            'reduction_amount_tax_excl',
                            'unit_price_tax_incl',
                            'unit_price_tax_excl',
                            'original_product_price'

                        );
                        foreach ($fields as $field)
                            $order_detail->{$field} = Tools::convertPriceFull($order_detail->{$field}, $old_currency, $currency);

                        $order_detail->update();
                        $order_detail->updateTaxAmount($order);
                    }

                    $id_order_carrier = (int)$order->getIdOrderCarrier();
                    if ($id_order_carrier) {
                        $order_carrier = $order_carrier = new OrderCarrier((int)$order->getIdOrderCarrier());
                        $order_carrier->shipping_cost_tax_excl = (float)Tools::convertPriceFull($order_carrier->shipping_cost_tax_excl, $old_currency, $currency);
                        $order_carrier->shipping_cost_tax_incl = (float)Tools::convertPriceFull($order_carrier->shipping_cost_tax_incl, $old_currency, $currency);
                        $order_carrier->update();
                    }

                    // Update order && order_invoice amount
                    $fields = array(
                        'total_discounts',
                        'total_discounts_tax_incl',
                        'total_discounts_tax_excl',
                        'total_discount_tax_excl',
                        'total_discount_tax_incl',
                        'total_paid',
                        'total_paid_tax_incl',
                        'total_paid_tax_excl',
                        'total_paid_real',
                        'total_products',
                        'total_products_wt',
                        'total_shipping',
                        'total_shipping_tax_incl',
                        'total_shipping_tax_excl',
                        'total_wrapping',
                        'total_wrapping_tax_incl',
                        'total_wrapping_tax_excl',
                    );

                    $invoices = $order->getInvoicesCollection();
                    if ($invoices)
                        foreach ($invoices as $invoice) {
                            foreach ($fields as $field)
                                if (isset($invoice->$field))
                                    $invoice->{$field} = Tools::convertPriceFull($invoice->{$field}, $old_currency, $currency);
                            $invoice->save();
                        }

                    foreach ($fields as $field)
                        if (isset($order->$field))
                            $order->{$field} = Tools::convertPriceFull($order->{$field}, $old_currency, $currency);

                    // Update currency in order
                    $order->id_currency = $currency->id;
                    // Update exchange rate
                    $order->conversion_rate = (float)$currency->conversion_rate;
                    $order->update();
                } else
                    $this->errors[] = Tools::displayError('You cannot change the currency.');
            } else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        } elseif (Tools::isSubmit('submitGenerateInvoice') && isset($order)) {
            if (!Configuration::get('PS_INVOICE', null, null, $order->id_shop))
                $this->errors[] = Tools::displayError('Invoice management has been disabled.');
            elseif ($order->hasInvoice())
                $this->errors[] = Tools::displayError('This order already has an invoice.');
            else {
                $order->setInvoice(true);
                Tools::redirectAdmin(self::$currentIndex . '&id_order=' . $order->id . '&vieworder&conf=4&token=' . $this->token);
            }
        } elseif (Tools::isSubmit('submitDeleteVoucher') && isset($order)) {
            if ($this->tabAccess['edit'] === '1') {
                $order_cart_rule = new OrderCartRule(Tools::getValue('id_order_cart_rule'));
                if (Validate::isLoadedObject($order_cart_rule) && $order_cart_rule->id_order == $order->id) {
                    if ($order_cart_rule->id_order_invoice) {
                        $order_invoice = new OrderInvoice($order_cart_rule->id_order_invoice);
                        if (!Validate::isLoadedObject($order_invoice))
                            throw new PrestaShopException('Can\'t load Order Invoice object');

                        // Update amounts of Order Invoice
                        $order_invoice->total_discount_tax_excl -= $order_cart_rule->value_tax_excl;
                        $order_invoice->total_discount_tax_incl -= $order_cart_rule->value;

                        $order_invoice->total_paid_tax_excl += $order_cart_rule->value_tax_excl;
                        $order_invoice->total_paid_tax_incl += $order_cart_rule->value;

                        // Update Order Invoice
                        $order_invoice->update();
                    }

                    // Update amounts of order
                    $order->total_discounts -= $order_cart_rule->value;
                    $order->total_discounts_tax_incl -= $order_cart_rule->value;
                    $order->total_discounts_tax_excl -= $order_cart_rule->value_tax_excl;

                    $order->total_paid += $order_cart_rule->value;
                    $order->total_paid_tax_incl += $order_cart_rule->value;
                    $order->total_paid_tax_excl += $order_cart_rule->value_tax_excl;

                    // Delete Order Cart Rule and update Order
                    $order_cart_rule->delete();
                    $order->update();
                    Tools::redirectAdmin(self::$currentIndex . '&id_order=' . $order->id . '&vieworder&conf=4&token=' . $this->token);
                } else
                    $this->errors[] = Tools::displayError('You cannot edit this cart rule.');
            } else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        } elseif (Tools::isSubmit('submitNewVoucher') && isset($order)) {
            if ($this->tabAccess['edit'] === '1') {
                if (!Tools::getValue('discount_name'))
                    $this->errors[] = Tools::displayError('You must specify a name in order to create a new discount.');
                else {
                    if ($order->hasInvoice()) {
                        // If the discount is for only one invoice
                        if (!Tools::isSubmit('discount_all_invoices')) {
                            $order_invoice = new OrderInvoice(Tools::getValue('discount_invoice'));
                            if (!Validate::isLoadedObject($order_invoice))
                                throw new PrestaShopException('Can\'t load Order Invoice object');
                        }
                    }

                    $cart_rules = array();
                    $discount_value = (float)str_replace(',', '.', Tools::getValue('discount_value'));
                    switch (Tools::getValue('discount_type')) {
                        // Percent type
                        case 1:
                            if ($discount_value < 100) {
                                if (isset($order_invoice)) {
                                    $cart_rules[$order_invoice->id]['value_tax_incl'] = Tools::ps_round($order_invoice->total_paid_tax_incl * $discount_value / 100, 2);
                                    $cart_rules[$order_invoice->id]['value_tax_excl'] = Tools::ps_round($order_invoice->total_paid_tax_excl * $discount_value / 100, 2);

                                    // Update OrderInvoice
                                    $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                } elseif ($order->hasInvoice()) {
                                    $order_invoices_collection = $order->getInvoicesCollection();
                                    foreach ($order_invoices_collection as $order_invoice) {
                                        $cart_rules[$order_invoice->id]['value_tax_incl'] = Tools::ps_round($order_invoice->total_paid_tax_incl * $discount_value / 100, 2);
                                        $cart_rules[$order_invoice->id]['value_tax_excl'] = Tools::ps_round($order_invoice->total_paid_tax_excl * $discount_value / 100, 2);

                                        // Update OrderInvoice
                                        $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                    }
                                } else {
                                    $cart_rules[0]['value_tax_incl'] = Tools::ps_round($order->total_paid_tax_incl * $discount_value / 100, 2);
                                    $cart_rules[0]['value_tax_excl'] = Tools::ps_round($order->total_paid_tax_excl * $discount_value / 100, 2);
                                }
                            } else
                                $this->errors[] = Tools::displayError('The discount value is invalid.');
                            break;
                        // Amount type
                        case 2:
                            if (isset($order_invoice)) {
                                if ($discount_value > $order_invoice->total_paid_tax_incl)
                                    $this->errors[] = Tools::displayError('The discount value is greater than the order invoice total.');
                                else {
                                    $cart_rules[$order_invoice->id]['value_tax_incl'] = Tools::ps_round($discount_value, 2);
                                    $cart_rules[$order_invoice->id]['value_tax_excl'] = Tools::ps_round($discount_value / (1 + ($order->getTaxesAverageUsed() / 100)), 2);

                                    // Update OrderInvoice
                                    $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                }
                            } elseif ($order->hasInvoice()) {
                                $order_invoices_collection = $order->getInvoicesCollection();
                                foreach ($order_invoices_collection as $order_invoice) {
                                    if ($discount_value > $order_invoice->total_paid_tax_incl)
                                        $this->errors[] = Tools::displayError('The discount value is greater than the order invoice total.') . $order_invoice->getInvoiceNumberFormatted(Context::getContext()->language->id, (int)$order->id_shop) . ')';
                                    else {
                                        $cart_rules[$order_invoice->id]['value_tax_incl'] = Tools::ps_round($discount_value, 2);
                                        $cart_rules[$order_invoice->id]['value_tax_excl'] = Tools::ps_round($discount_value / (1 + ($order->getTaxesAverageUsed() / 100)), 2);

                                        // Update OrderInvoice
                                        $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                    }
                                }
                            } else {
                                if ($discount_value > $order->total_paid_tax_incl)
                                    $this->errors[] = Tools::displayError('The discount value is greater than the order total.');
                                else {
                                    $cart_rules[0]['value_tax_incl'] = Tools::ps_round($discount_value, 2);
                                    $cart_rules[0]['value_tax_excl'] = Tools::ps_round($discount_value / (1 + ($order->getTaxesAverageUsed() / 100)), 2);
                                }
                            }
                            break;
                        // Free shipping type
                        case 3:
                            if (isset($order_invoice)) {
                                if ($order_invoice->total_shipping_tax_incl > 0) {
                                    $cart_rules[$order_invoice->id]['value_tax_incl'] = $order_invoice->total_shipping_tax_incl;
                                    $cart_rules[$order_invoice->id]['value_tax_excl'] = $order_invoice->total_shipping_tax_excl;

                                    // Update OrderInvoice
                                    $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                }
                            } elseif ($order->hasInvoice()) {
                                $order_invoices_collection = $order->getInvoicesCollection();
                                foreach ($order_invoices_collection as $order_invoice) {
                                    if ($order_invoice->total_shipping_tax_incl <= 0)
                                        continue;
                                    $cart_rules[$order_invoice->id]['value_tax_incl'] = $order_invoice->total_shipping_tax_incl;
                                    $cart_rules[$order_invoice->id]['value_tax_excl'] = $order_invoice->total_shipping_tax_excl;

                                    // Update OrderInvoice
                                    $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                }
                            } else {
                                $cart_rules[0]['value_tax_incl'] = $order->total_shipping_tax_incl;
                                $cart_rules[0]['value_tax_excl'] = $order->total_shipping_tax_excl;
                            }
                            break;
                        default:
                            $this->errors[] = Tools::displayError('The discount type is invalid.');
                    }

                    $res = true;
                    foreach ($cart_rules as &$cart_rule) {
                        $cartRuleObj = new CartRule();
                        $cartRuleObj->date_from = date('Y-m-d H:i:s', strtotime('-1 hour', strtotime($order->date_add)));
                        $cartRuleObj->date_to = date('Y-m-d H:i:s', strtotime('+1 hour'));
                        $cartRuleObj->name[Configuration::get('PS_LANG_DEFAULT')] = Tools::getValue('discount_name');
                        $cartRuleObj->quantity = 0;
                        $cartRuleObj->quantity_per_user = 1;
                        if (Tools::getValue('discount_type') == 1)
                            $cartRuleObj->reduction_percent = $discount_value;
                        elseif (Tools::getValue('discount_type') == 2)
                            $cartRuleObj->reduction_amount = $cart_rule['value_tax_excl'];
                        elseif (Tools::getValue('discount_type') == 3)
                            $cartRuleObj->free_shipping = 1;
                        $cartRuleObj->active = 0;
                        if ($res = $cartRuleObj->add())
                            $cart_rule['id'] = $cartRuleObj->id;
                        else
                            break;
                    }

                    if ($res) {
                        foreach ($cart_rules as $id_order_invoice => $cart_rule) {
                            // Create OrderCartRule
                            $order_cart_rule = new OrderCartRule();
                            $order_cart_rule->id_order = $order->id;
                            $order_cart_rule->id_cart_rule = $cart_rule['id'];
                            $order_cart_rule->id_order_invoice = $id_order_invoice;
                            $order_cart_rule->name = Tools::getValue('discount_name');
                            $order_cart_rule->value = $cart_rule['value_tax_incl'];
                            $order_cart_rule->value_tax_excl = $cart_rule['value_tax_excl'];
                            $res &= $order_cart_rule->add();

                            $order->total_discounts += $order_cart_rule->value;
                            $order->total_discounts_tax_incl += $order_cart_rule->value;
                            $order->total_discounts_tax_excl += $order_cart_rule->value_tax_excl;
                            $order->total_paid -= $order_cart_rule->value;
                            $order->total_paid_tax_incl -= $order_cart_rule->value;
                            $order->total_paid_tax_excl -= $order_cart_rule->value_tax_excl;
                        }

                        // Update Order
                        $res &= $order->update();
                    }

                    if ($res)
                        Tools::redirectAdmin(self::$currentIndex . '&id_order=' . $order->id . '&vieworder&conf=4&token=' . $this->token);
                    else
                        $this->errors[] = Tools::displayError('An error occurred during the OrderCartRule creation');
                }
            } else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        }

        AdminControllerCore::postProcess();
    }
}
