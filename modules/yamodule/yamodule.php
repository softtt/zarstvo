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

class Yamodule extends PaymentModule
{
    private $p2p_status = '';
    private $mws_status = '';
    private $org_status = '';
    private $market_status = '';
    private $metrika_status = '';
    private $pokupki_status = '';
    private $metrika_valid;
    private $update_status;
    private $update_text;

    public $cryptor;

    public $status = array(
        'DELIVERY' => 900,
        'CANCELLED' => 901,
        'PICKUP' => 902,
        'PROCESSING' => 903,
        'DELIVERED' => 904,
        'UNPAID' => 905,
        'RESERVATION_EXPIRED' => 906,
        'RESERVATION' => 907
    );

    public static $ModuleRoutes = array(
        'pokupki_cart' => array(
            'controller' => 'pokupki',
            'rule' =>  'yamodule/{controller}/{type}',
            'keywords' => array(
                'type'   => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'type'),
                'module'  => array('regexp' => '[\w]+', 'param' => 'module'),
                'controller' => array('regexp' => '[\w]+',  'param' => 'controller')
            ),
            'params' => array(
                'fc' => 'module',
                'module' => 'yamodule',
                'controller' => 'pokupki'
            )
        ),
        'pokupki_order' => array(
            'controller' => 'pokupki',
            'rule' =>  'yamodule/{controller}/{type}/{func}',
            'keywords' => array(
                'type'   => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'type'),
                'func'   => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'func'),
                'module'  => array('regexp' => '[\w]+', 'param' => 'module'),
                'controller' => array('regexp' => '[\w]+',  'param' => 'controller')
            ),
            'params' => array(
                'fc' => 'module',
                'module' => 'yamodule',
                'controller' => 'pokupki'
            )
        ),
        'generate_price' => array(
            'controller' => null,
            'rule' =>  'yamodule/{controller}',
            'keywords' => array(
                'controller' => array('regexp' => '[\w]+',  'param' => 'controller')
            ),
            'params' => array(
                'fc' => 'module',
                'module' => 'yamodule',
            )
        ),
    );

    public function hookModuleRoutes()
    {
        return self::$ModuleRoutes;
    }

    public function __construct()
    {
        if (!defined('_PS_VERSION_')) {
            exit;
        }

        include_once(dirname(__FILE__).'/classes/mws.php');
        include_once(dirname(__FILE__).'/classes/oc.php');
        include_once(dirname(__FILE__).'/classes/partner.php');
        include_once(dirname(__FILE__).'/classes/ymlclass.php');
        include_once(dirname(__FILE__).'/classes/hforms.php');
        include_once(dirname(__FILE__).'/classes/callback.php');
        include_once(dirname(__FILE__).'/lib/api.php');
        include_once(dirname(__FILE__).'/lib/external_payment.php');

        $this->name = 'yamodule';
        $this->tab = 'payments_gateways';
        $this->version = '1.3.9';
        $this->author = 'Яндекс.Деньги';
        $this->need_instance = 1;
        $this->bootstrap = 1;
        $this->module_key = "f51f5c45095c7d4eec9d2266901d793e";
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        parent::__construct();

        $this->cryptor = $this->getCryptor();
        $this->displayName = $this->l('Y.CMS Prestashop');
        $this->description = $this->l(
            'Yandex.Money, Yandex.Service, Yandex.Metrika, Yandex.Market Orders in the Market'
        );
        $this->confirmUninstall = $this->l('Really uninstall the module?');
        if (!sizeof(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('There is no set currency for your module!');
        }
    }

    public function getCryptor()
    {
        if (!Configuration::get('PS_CIPHER_ALGORITHM') || !defined('_RIJNDAEL_KEY_')) {
            $cipher_tool = new Blowfish(_COOKIE_KEY_, _COOKIE_IV_);
        } else {
            $cipher_tool = new Rijndael(_RIJNDAEL_KEY_, _RIJNDAEL_IV_);
        }

        return $cipher_tool;

        // $cache_data = unserialize($cryptor->decrypt($db_cache));
        // $data = $cryptor->encrypt(serialize($data));
    }

    public function multiLangField($str)
    {
        $languages = Language::getLanguages(false);
        $data = array();
        foreach ($languages as $lang) {
            $data[$lang['id_lang']] = $str;
        }

        return $data;
    }

    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('displayPayment')
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('displayFooter')
            || !$this->registerHook('displayHeader')
            || !$this->registerHook('ModuleRoutes')
            || !$this->registerHook('displayOrderConfirmation')
            || !$this->registerHook('displayAdminOrder')
            || !$this->registerHook('actionOrderStatusUpdate')
        ) {
            return false;
        }

        $status = array(
            'DELIVERY' => array(
                'name' => 'YA Ждёт отправки',
                'color' => '#8A2BE2',
                'id' => 900,
                'paid' => true,
                'shipped' => false,
                'logable' => true,
                'delivery' => true
            ),
            'CANCELLED' => array(
                'name' => 'YA Отменен',
                'color' => '#b70038',
                'id' => 901,
                'paid' => false,
                'shipped' => false,
                'logable' => true,
                'delivery' => false
            ),
            'PICKUP' => array(
                'name' => 'YA В пункте самовывоза',
                'color' => '#cd98ff',
                'id' => 902,
                'paid' => true,
                'shipped' => true,
                'logable' => true,
                'delivery' => true
            ),
            'PROCESSING' => array(
                'name' => 'YA В процессе подготовки',
                'color' => '#FF8C00',
                'id' => 903,
                'paid' => true,
                'shipped' => false,
                'logable' => false,
                'delivery' => true
            ),
            'DELIVERED' => array(
                'name' => 'YA Доставлен',
                'color' => '#108510',
                'id' => 904,
                'paid' => true,
                'shipped' => true,
                'logable' => true,
                'delivery' => true
            ),
            'UNPAID' => array(
                'name' => 'YA Не оплачен',
                'color' => '#ff1c30',
                'id' => 905,
                'paid' => false,
                'shipped' => false,
                'logable' => false,
                'delivery' => false
            ),
            'RESERVATION_EXPIRED' => array(
                'name' => 'YA Резерв отменён',
                'color' => '#ff2110',
                'id' => 906,
                'paid' => false,
                'shipped' => false,
                'logable' => false,
                'delivery' => false
            ),
            'RESERVATION' => array(
                'name' => 'YA Резерв',
                'color' => '#0f00d3',
                'id' => 907,
                'paid' => false,
                'shipped' => false,
                'logable' => false,
                'delivery' => false
            ),
        );

        foreach ($status as $s) {
            $os = new OrderState((int)$s['id']);
            $os->id = $s['id'];
            $os->force_id = true;
            $os->name = $this->multiLangField($s['name']);
            $os->color = $s['color'];
            $os->module_name = $this->name;
            $os->paid = $s['paid'];
            $os->logable = $s['logable'];
            $os->shipped = $s['shipped'];
            $os->delivery = $s['delivery'];
            $os->add();
        }

        $sql = array();
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'pokupki_orders`
            (
                `id_order` int(10) NOT NULL,
                `id_market_order` varchar(100) NOT NULL,
                `currency` varchar(100) NOT NULL,
                `ptype` varchar(100) NOT NULL,
                `home` varchar(100) NOT NULL,
                `pmethod` varchar(100) NOT NULL,
                `outlet` varchar(100) NOT NULL,
                PRIMARY KEY  (`id_order`,`id_market_order`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mws_return`
            (
                `id_return` int(10) NOT NULL AUTO_INCREMENT,
                `invoice_id` varchar(128) NOT NULL,
                `cause` varchar(256) NOT NULL,
                `amount` DECIMAL(10,2) NOT NULL,
                `request` varchar(1024) NOT NULL,
                `response` varchar(1024) NOT NULL,
                `status` varchar(1024) NOT NULL,
                `error` varchar(1024) NOT NULL,
                `date` datetime NOT NULL,
                PRIMARY KEY  (`id_return`,`invoice_id`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci';

        foreach ($sql as $qr) {
            Db::getInstance()->execute($qr);
        }

        $customer = new Customer();
        $customer->firstname = 'YA POKUPKI Not Delete';
        $customer->lastname = 'YA POKUPKI Not Delete';
        $customer->email = 'support@supp.com';
        $customer->passwd = Tools::encrypt('OPC123456dmo');
        $customer->newsletter = 1;
        $customer->optin = 1;
        $customer->active = 0;
        $customer->add();
        Configuration::updateValue('YA_POKUPKI_CUSTOMER', $customer->id);
        Configuration::updateValue('YA_ORG_INSIDE', 0);

        return true;
    }

    public function uninstall()
    {
        $id = Configuration::get('YA_POKUPKI_CUSTOMER');
        $customer = new Customer($id);
        $customer->id = $id;
        $customer->delete();
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'pokupki_orders');

        foreach ($this->status as $s) {
            $os = new OrderState((int)$s);
            $os->id = $s;
            $os->delete();
        }

        return parent::uninstall();
    }

    public function hookdisplayAdminOrder($params)
    {
        $ya_order_db = $this->getYandexOrderById((int)$params['id_order']);
        $html = '';
        if ($ya_order_db['id_market_order']) {
            $partner = new Partner();
            $ya_order = $partner->getOrder($ya_order_db['id_market_order']);
            if ($ya_order) {
                $array = array();
                $state = $ya_order->order->status;
                if ($state == 'PROCESSING') {
                    $array = array(
                        $this->status['RESERVATION_EXPIRED'],
                        $this->status['RESERVATION'],
                        $this->status['PROCESSING'],
                        $this->status['DELIVERED'],
                        $this->status['PICKUP'],
                        $this->status['UNPAID']
                    );
                } elseif ($state == 'DELIVERY') {
                    $array = array(
                        $this->status['RESERVATION_EXPIRED'],
                        $this->status['RESERVATION'],
                        $this->status['PROCESSING'],
                        $this->status['DELIVERY'],
                        $this->status['UNPAID']
                    );
                    if (!isset($ya_order->order->delivery->outletId)
                        || $ya_order->order->delivery->outletId < 1
                        || $ya_order->order->delivery->outletId == ''
                    ) {
                        $array[] = $this->status['PICKUP'];
                    }
                } elseif ($state == 'PICKUP') {
                    $array = array(
                        $this->status['RESERVATION_EXPIRED'],
                        $this->status['RESERVATION'],
                        $this->status['PROCESSING'],
                        $this->status['PICKUP'],
                        $this->status['DELIVERY'],
                        $this->status['UNPAID']
                    );
                } else {
                    $array = array(
                        $this->status['RESERVATION_EXPIRED'],
                        $this->status['RESERVATION'],
                        $this->status['PROCESSING'],
                        $this->status['DELIVERED'],
                        $this->status['PICKUP'],
                        $this->status['CANCELLED'],
                        $this->status['DELIVERY'],
                        $this->status['UNPAID']
                    );
                }
            }
        } else {
            $array = array(
                $this->status['RESERVATION_EXPIRED'],
                $this->status['RESERVATION'],
                $this->status['PROCESSING'],
                $this->status['DELIVERED'],
                $this->status['PICKUP'],
                $this->status['CANCELLED'],
                $this->status['DELIVERY'],
                $this->status['UNPAID']
            );
        }

        $array = Tools::jsonEncode($array);
        $html .= '<script type="text/javascript">
            $(document).ready(function(){
                var array = JSON.parse("'.$array.'");
                for(var k in array){
                    $("#id_order_state option[value="+ array[k] +"]").attr({disabled: "disabled"});
                };

                $("#id_order_state").trigger("chosen:updated");
            });
        </script>';

        // if(Configuration::get('YA_POKUPKI_SET_CHANGEC') && $ya_order->order->paymentType != 'PREPAID')
        if (Configuration::get('YA_POKUPKI_SET_CHANGEC')) {
            $html .= $this->displayTabContent($params['id_order']);
        }

        return $html;
    }

    public function displayReturnsContent($params)
    {
        $errors = array();

        if (!Configuration::get('YA_ORG_SHOPID')) {
            $errors[] = $this->l('Module Yandex.The cashier is empty, the shop ID (shopId)');
        }
        if (!Configuration::get('YA_ORG_ACTIVE')) {
            $errors[] = $this->l('Module Yandex.Cash is disabled');
        }

        $mws = new Mws();
        $mws->demo = !Configuration::get('YA_ORG_TYPE');
        $mws->shopId = Configuration::get('YA_ORG_SHOPID');
        $mws->PkeyPem = Configuration::get('yamodule_mws_pkey');
        $mws->CertPem = Configuration::get('yamodule_mws_cert');

        $success = false;
        $mws_payment = $mws->request(
            'listOrders',
            array(
                'orderNumber' => 'KASSA_'.$params['order']->id_cart
            ),
            false,
            false
        );
        if (!isset($mws_payment['invoiceId']) || !$mws_payment['invoiceId']) {
            $errors[] = $this->l(
                'The problem with the certificate, no payment under this order or specified wrong ID of the store'
            );
        }

        if (empty($errors) && Tools::isSubmit('return_sum')) {
            $cause = Tools::getValue('return_cause');
            $amount = Tools::getValue('return_sum');
            $amount = number_format((float)$amount, 2, '.', '');

            if (Tools::strlen($cause) > 100 || Tools::strlen($cause) < 3) {
                $errors[] = $this->l('Return reason can not be empty or exceed a length of 100 characters');
            }
            if ($amount > $mws_payment['orderSumAmount']) {
                $errors[] = $this->l('The refund amount cannot exceed the amount of the payment');
            }

            if (!$errors) {
                $mws_return = $mws->request(
                    'returnPayment',
                    array(
                        'invoiceId' => $mws_payment['invoiceId'],
                        'amount' => $amount,
                        'cause' => $cause
                    )
                );

                if (isset($mws_return['status'])) {
                    $mws->addReturn(array(
                        'amount' => $amount,
                        'cause' => $cause,
                        'request' => $mws->txt_request || 'NULL',
                        'response' => $mws->txt_respond || 'NULL',
                        'status' => $mws_return['status'],
                        'error' => $mws_return['error'],
                        'invoice_id' => $mws_payment['invoiceId'],
                        'date' => date('Y-m-d H:i:s')
                    ));

                    if ($mws_return['status'] == 0) {
                        $success = true;
                    } else {
                        $errors[] = $this->getErr($mws_return['error']);
                    }
                }
            }
        }

        $kassa_returns = array();
        $docs = $params['order']->getDocuments();
        $payment = $params['order']->getOrderPaymentCollection();

        $inv = (isset($mws_payment['invoiceId'])) ? $mws_payment['invoiceId'] : 0;
        $inv_sum = (isset($mws_payment['orderSumAmount'])) ? $mws_payment['orderSumAmount'] : 0;
        $inv_type = (isset($mws_payment['paymentType'])) ? $mws_payment['paymentType'] : "none";
        $ri = $mws->getSuccessReturns($inv);
        $sum_returned = $mws->sum;

        $this->context->smarty->assign(array(
            'id_order' => $params['order']->id,
            'kassa_returns' => $kassa_returns,
            'return_total' => Tools::displayPrice($sum_returned),
            'return_sum' => Tools::displayPrice($inv_sum - $sum_returned),
            'invoiceId' => $inv,
            'return_items' => $ri,
            'payment_method' => $payment[0]->payment_method." (".$inv_type.")",
            'return_success' => $success,
            'text_success' => $this->l('The payment is successfully returned'),
            'return_errors' => $errors,
            'doc' => $docs[0],
        ));

        $html = $this->display(__FILE__, 'kassa_returns_content.tpl');

        return $html;
    }

    public function displayReturnsContentTabs()
    {
        $html = $this->display(__FILE__, 'kassa_returns_tabs.tpl');

        return $html;
    }

    public function sendCarrierToYandex($order)
    {
        $order_ya = $this->getYandexOrderById($order->id);
        if ($order_ya['id_order']
            && $order_ya['home'] != ''
            && $order_ya['id_market_order']
            && in_array($order->current_state, $this->status)
        ) {
            $partner = new Partner();
            $partner->sendDelivery($order);
        }
    }

    public function hookactionOrderStatusUpdate($params)
    {
        $new_os = $params['newOrderStatus'];
        $status_flip = array_flip($this->status);
        if (in_array($new_os->id, $this->status)) {
            $ya_order_db = $this->getYandexOrderById((int)$params['id_order']);
            $id_ya_order = $ya_order_db['id_market_order'];
            if ($id_ya_order) {
                $partner = new Partner();
                $ya_order = $partner->getOrder($id_ya_order);
                $state = $ya_order->order->status;
                if ($state == 'PROCESSING'
                    && ($new_os->id == $this->status['DELIVERY']
                        || $new_os->id == $this->status['CANCELLED'])
                ) {
                    $partner->sendOrder($status_flip[$new_os->id], $id_ya_order);
                } elseif ($state == 'DELIVERY'
                    && ($new_os->id == $this->status['DELIVERED']
                        || $new_os->id == $this->status['PICKUP']
                        || $new_os->id == $this->status['CANCELLED'])
                ) {
                    $partner->sendOrder($status_flip[$new_os->id], $id_ya_order);
                } elseif ($state == 'PICKUP'
                    && ($new_os->id == $this->status['DELIVERED'] || $new_os->id == $this->status['CANCELLED'])
                ) {
                    $partner->sendOrder($status_flip[$new_os->id], $id_ya_order);
                } elseif ($state == 'RESERVATION_EXPIRED' || $state == 'RESERVATION') {
                    return false;
                } else {
                    return false;
                }
            }
        }
    }

    public function getYandexOrderById($id)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('pokupki_orders');
        $query->where('id_order = '.(int)$id);
        $svp = Db::getInstance()->GetRow($query);

        return $svp;
    }

    public function getOrderByYaId($id)
    {
        $query = new DbQuery();
        $query->select('id_order');
        $query->from('pokupki_orders');
        $query->where('id_market_order = '.(int)$id);
        $svp = Db::getInstance()->GetRow($query);
        $order = new Order((int)$svp['id_order']);

        return $order;
    }

    public function displayTabContent($id)
    {
        $partner = new Partner();
        $order_ya_db = $this->getYandexOrderById($id);
        $html = '';
        if ($order_ya_db['id_market_order']) {
            $ya_order = $partner->getOrder($order_ya_db['id_market_order']);
            $types = unserialize(Configuration::get('YA_POKUPKI_CARRIER_SERIALIZE'));
            $state = $ya_order->order->status;
            $st = array('PROCESSING', 'DELIVERY', 'PICKUP');
            // Tools::d($ya_order);
            if (!in_array($state, $st)) {
                return false;
            }

            $this->context->controller->AddJS($this->_path.'/views/js/back.js');
            $this->context->controller->AddCss($this->_path.'/views/css/back.css');
            $order = new Order($id);
            $cart = new Cart($order->id_cart);
            $carriers = $cart->simulateCarriersOutput();
            $html = '';
            $i = 1;
            $tmp = array();
            $tmp[0]['id_carrier'] = 0;
            $tmp[0]['name'] = $this->l('-- Please select carrier --');
            foreach ($carriers as $c) {
                $id = str_replace(',', '', Cart::desintifier($c['id_carrier']));
                $type = isset($types[$id]) ? $types[$id] : 'POST';
                if (!Configuration::get('YA_MARKET_SET_ROZNICA') && $type == 'PICKUP') {
                    continue;
                }

                $tmp[$i]['id_carrier'] = $id;
                $tmp[$i]['name'] = $c['name'];
                $i++;
            }

            if (count($tmp) <= 1) {
                return false;
            }

            $fields_form = array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Carrier Available'),
                        'icon' => 'icon-cogs'
                    ),
                    'input' => array(
                        'sel_delivery' => array(
                            'type' => 'select',
                            'label' => $this->l('Carrier'),
                            'name' => 'new_carrier',
                            'required' => true,
                            'default_value' => 0,
                            'class' => 't sel_delivery',
                            'options' => array(
                                'query' => $tmp,
                                'id' => 'id_carrier',
                                'name' => 'name'
                            )
                        ),
                        array(
                            'col' => 3,
                            'class' => 't pr_in',
                            'type' => 'text',
                            'desc' => $this->l('Carrier price tax incl.'),
                            'name' => 'price_incl',
                            'label' => $this->l('Price tax incl.'),
                        ),
                        array(
                            'col' => 3,
                            'class' => 't pr_ex',
                            'type' => 'text',
                            'desc' => $this->l('Carrier price tax excl.'),
                            'name' => 'price_excl',
                            'label' => $this->l('Price tax excl.'),
                        ),
                    ),
                    'buttons' => array(
                        'updcarrier' => array(
                            'title' => $this->l('Update carrier'),
                            'name' => 'updcarrier',
                            'type' => 'button',
                            'class' => 'btn btn-default pull-right changec_submit',
                            'icon' => 'process-icon-refresh'
                        )
                    )
                ),
            );

            $helper = new HelperForm();
            $helper->show_toolbar = false;
            $helper->table = $this->table;
            $helper->module = $this;
            $helper->identifier = $this->identifier;
            $helper->submit_action = 'submitChangeCarrier';
            $helper->currentIndex = AdminController::$currentIndex.'?id_order='.$order->id
                .'&vieworder&token='.Tools::getAdminTokenLite('AdminOrders');
            $helper->token = Tools::getAdminTokenLite('AdminOrders');
            $helper->tpl_vars['fields_value']['price_excl'] = '';
            $helper->tpl_vars['fields_value']['price_incl'] = '';
            $helper->tpl_vars['fields_value']['new_carrier'] = 0;
            $path_module_http = __PS_BASE_URI__.'modules/yamodule/';
            $html .= '<div class="change_carr">
                    <script type="text/javascript">
                        var notselc = "'.$this->l('Please select carrier').'";
                        var ajaxurl = "'.$path_module_http.'";
                        var idm = "'.(int)$this->context->employee->id.'";
                        var tkn = "'.Tools::getAdminTokenLite('AdminOrders').'";
                        var id_order = "'.(int)$order->id.'";
                    </script>
                    <div id="circularG">
                        <div id="circularG_1" class="circularG"></div>
                        <div id="circularG_2" class="circularG"></div>
                        <div id="circularG_3" class="circularG"></div>
                        <div id="circularG_4" class="circularG"></div>
                        <div id="circularG_5" class="circularG"></div>
                        <div id="circularG_6" class="circularG"></div>
                        <div id="circularG_7" class="circularG"></div>
                        <div id="circularG_8" class="circularG"></div>
                    </div>';
            $html .= $helper->generateForm(array($fields_form)).'</div>';
        }

        return $html;
    }

    public function processLoadPrice()
    {
        $id_order = (int)Tools::getValue('id_o');
        $id_new_carrier = (int)Tools::getValue('new_carrier');
        $order = new Order($id_order);
        $cart = new Cart($order->id_cart);
        $carrier_list = $cart->getDeliveryOptionList();
        $result = array();
        if (isset($carrier_list[$order->id_address_delivery][$id_new_carrier.',']['carrier_list'][$id_new_carrier])) {
            $carrier = $carrier_list[$order->id_address_delivery][$id_new_carrier.',']['carrier_list'][$id_new_carrier];
            $pr_incl = $carrier['price_with_tax'];
            $pr_excl = $carrier['price_without_tax'];
            $result = array(
                'price_without_tax' => $pr_excl,
                'price_with_tax' => $pr_incl
            );
        } else {
            $result = array('error' => $this->l('Wrong carrier'));
        }

        return $result;
    }

    public function processChangeCarrier()
    {
        $id_order = (int)Tools::getValue('id_o');
        $id_new_carrier = (int)Tools::getValue('new_carrier');
        $price_incl = (float)Tools::getValue('pr_incl');
        $price_excl = (float)Tools::getValue('pr_excl');
        $order = new Order($id_order);
        $result = array();
        $result['error'] = '';
        if ($id_new_carrier == 0) {
            $result['error'] = $this->l('Error: cannot select carrier');
        } else {
            if ($order->id < 1) {
                $result['error'] = $this->l('Error: cannot find order');
            } else {
                $total_carrierwt = (float)$order->total_products_wt + (float)$price_incl;
                $total_carrier = (float)$order->total_products + (float)$price_excl;

                $order->total_paid = (float)$total_carrierwt;
                $order->total_paid_tax_incl = (float)$total_carrierwt;
                $order->total_paid_tax_excl =(float)$total_carrier;
                $order->total_paid_real = (float)$total_carrierwt;
                $order->total_shipping = (float)$price_incl;
                $order->total_shipping_tax_excl = (float)$price_excl;
                $order->total_shipping_tax_incl = (float)$price_incl;
                $order->carrier_tax_rate = (float)$order->carrier_tax_rate;
                $order->id_carrier = (int)$id_new_carrier;
                if (!$order->update()) {
                    $result['error'] = $this->l('Error: cannot update order');
                    $result['status'] = false;
                } else {
                    if ($order->invoice_number > 0) {
                        $order_invoice = new OrderInvoice($order->invoice_number);
                        $order_invoice->total_paid_tax_incl =(float)$total_carrierwt;
                        $order_invoice->total_paid_tax_excl =(float)$total_carrier;
                        $order_invoice->total_shipping_tax_excl =(float)$price_excl;
                        $order_invoice->total_shipping_tax_incl =(float)$price_incl;
                        if (!$order_invoice->update()) {
                            $result['error'] = $this->l('Error: cannot update order invoice');
                            $result['status'] = false;
                        }
                    }

                    $id_order_carrier = Db::getInstance()->getValue('
                            SELECT `id_order_carrier`
                            FROM `'._DB_PREFIX_.'order_carrier`
                            WHERE `id_order` = '.(int) $order->id);

                    if ($id_order_carrier) {
                        $order_carrier = new OrderCarrier($id_order_carrier);
                        $order_carrier->id_carrier = $order->id_carrier;
                        $order_carrier->shipping_cost_tax_excl = (float)$price_excl;
                        $order_carrier->shipping_cost_tax_incl = (float)$price_incl;
                        if (!$order_carrier->update()) {
                            $result['error'] = $this->l('Error: cannot update order carrier');
                            $result['status'] = false;
                        }
                    }

                    $result['status'] = true;
                }
            }
        }

        if ($result['status']) {
            $this->sendCarrierToYandex($order);
        }

        return $result;
    }

    public function hookdisplayFooter($params)
    {
        $data = '';
        if (!Configuration::get('YA_METRIKA_ACTIVE')) {
            $data .= 'var celi_order = false;';
            $data .= 'var celi_cart = false;';
            $data .= 'var celi_wishlist = false;';
            return '<p style="display:none;"><script type="text/javascript">'.$data.'</script></p>';
        }

        if (Configuration::get('YA_METRIKA_CELI_ORDER')) {
            $data .= 'var celi_order = true;';
        } else {
            $data .= 'var celi_order = false;';
        }

        if (Configuration::get('YA_METRIKA_CELI_CART')) {
            $data .= 'var celi_cart = true;';
        } else {
            $data .= 'var celi_cart = false;';
        }

        if (Configuration::get('YA_METRIKA_CELI_WISHLIST')) {
            $data .= 'var celi_wishlist = true;';
        } else {
            $data .= 'var celi_wishlist = false;';
        }

        if (Configuration::get('YA_METRIKA_CODE') != '') {
            return '<p style="display:none;"><script type="text/javascript">'.$data
            .'</script>'.Configuration::get('YA_METRIKA_CODE').'</p>';
        }
    }

    public function makeData($product, $combination = false)
    {
        $params = array();
        $data = array();
        $images = array();
        $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        if ($combination) {
            $quantity = (int)$combination['quantity'];
            $url = $product['link'].'#'.$combination['comb_url'];
            $price =  Tools::ps_round($combination['price'], 2);
            $reference = $combination['reference'];
            $id_offer = $product['id_product'].'c'.$combination['id_product_attribute'];
            $barcode = $combination['ean13'];
            $images = Image::getImages($id_lang, $product['id_product'], $combination['id_product_attribute']);
            if (empty($images)) {
                $images = Image::getImages($id_lang, $product['id_product']);
            }

            if ((int)$combination['weight'] > 0) {
                $data['weight'] = $combination['weight'];
                $data['weight'] = number_format($data['weight'], 2);
            } else {
                $data['weight'] = $product['weight'];
                $data['weight'] = number_format($data['weight'], 2);
            }

            if ($combination['minimal_quantity'] > 1) {
                $data['sales_notes'] = $this->l('Minimum order').' '.$combination['minimal_quantity'].' '.
                    $this->l('of the product (s)');
            }
            $data['group_id'] = $product['id_product'];
        } else {
            $quantity = (int)$product['quantity'];
            $url = $product['link'];
            $price =  Tools::ps_round($product['price'], 2);
            $reference = $product['reference'];
            $id_offer = $product['id_product'];
            $barcode = $product['ean13'];
            $images = Image::getImages($id_lang, $product['id_product']);
            if ((int)$product['weight'] > 0) {
                $data['weight'] = $product['weight'];
                $data['weight'] = number_format($data['weight'], 2);
            }

            if ($product['minimal_quantity'] > 1) {
                $data['sales_notes'] = $this->l('Minimum order').' '.$product['minimal_quantity'].' '.
                    $this->l('of the product (s)');
            }
        }

        if (Configuration::get('YA_MARKET_SET_AVAILABLE')) {
            if ($quantity < 1) {
                return;
            }
        }

        $available = 'false';
        if ($this->yamarket_availability == 0) {
            $available = 'true';
        } elseif ($this->yamarket_availability == 1) {
            if ($quantity > 0) {
                $available = 'true';
            }
        } elseif ($this->yamarket_availability == 2) {
            $available = 'true';
            if ($quantity == 0) {
                return;
            }
        }


        if ($product['features']) {
            foreach ($product['features'] as $feature) {
                $params[$feature['name']] = $feature['value'];
            }
        }
        if ($combination) {
            $params = array_merge($params, $combination['attributes']);
        }

        $data['available'] = $available;
        $data['url'] = str_replace('https://', 'http://', $url);
        $data['id'] = $id_offer;
        $data['currencyId'] = $this->currency_iso;
        $data['price'] = $price;
        $data['categoryId'] = $product['id_category_default'];

        /*-------------------------------------------------------------------*/
        preg_match_all('/([а-яё]+)/iu', $data['url'], $urlarr, PREG_SET_ORDER);
        if (!empty($urlarr)) {
            foreach ($urlarr as $ua) {
                $data['url'] = str_replace($ua[0], rawurlencode($ua[0]), $data['url']);
            }
        }
        /*-------------------------------------------------------------------*/
        foreach ($images as $i) {
            $uri = $this->context->link->getImageLink($product['link_rewrite'], $i['id_image']);
            preg_match_all('/([а-яё]+)/iu', $uri, $marr, PREG_SET_ORDER);
            if (!empty($marr)) {
                foreach ($marr as $m) {
                    $uri = str_replace($m[0], rawurlencode($m[0]), $uri);
                }
            }

            $data['picture'][] = $uri;
        }

        if (!Configuration::get('YA_MARKET_SHORT')) {
            $data['model'] = $product['name'];
            if (Configuration::get('YA_MARKET_SET_DIMENSIONS')
                && $product['height'] > 0
                && $product['depth'] > 0
                && $product['width']
            ) {
                $data['dimensions'] = number_format($product['depth'], 3, '.', '').
                    '/'.number_format($product['width'], 3, '.', '')
                    .'/'.number_format($product['height'], 3, '.', '');
            }
            if ($product['is_virtual']) {
                $data['downloadable'] = 'true';
            } else {
                $data['downloadable'] = 'false';
            }
            if (Configuration::get('YA_MARKET_DESC_TYPE')) {
                $data['description'] = $product['description_short'];
            } else {
                $data['description'] = $product['description'];
            }
            $data['param'] = $params;
        } else {
            $data['name'] = $product['name'];
        }

        $data['vendor'] = $product['manufacturer_name'];
        $data['barcode'] = $barcode;
        $data['delivery'] = 'false';
        $data['pickup'] = 'false';
        $data['store'] = 'false';
        $data['vendorCode'] = $reference;
        if (Configuration::get('YA_MARKET_SET_DOST')) {
            $data['delivery'] = 'true';
        }
        if (Configuration::get('YA_MARKET_SET_SAMOVIVOZ')) {
            $data['pickup'] = 'true';
        }
        if (Configuration::get('YA_MARKET_SET_ROZNICA')) {
            $data['store'] = 'true';
        }

        return $data;
    }

    public function generateXML($cron)
    {
        $shop_url = 'http://'.Tools::getHttpHost(false, true).__PS_BASE_URI__;
        $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $currency_default = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        $this->currency_iso = $currency_default->iso_code;
        $country = new Country(Configuration::get('PS_COUNTRY_DEFAULT'));
        $this->country_name = $country->name;
        $currencies = Currency::getCurrencies();
        $categories = Category::getCategories($id_lang, false, false);
        $yamarket_set_combinations = Configuration::get('YA_MARKET_SET_COMBINATIONS');
        $this->yamarket_availability = Configuration::get('YA_MARKET_DOSTUPNOST');
        $this->gzip = Configuration::get('YA_MARKET_SET_GZIP');

        /*-----------------------------------------------------------------------------*/

        $cats = array();
        if ($c = Configuration::get('YA_MARKET_CATEGORIES')) {
            $uc = unserialize($c);
            if (is_array($uc)) {
                $cats = $uc;
            }
        }

        $yml = new Yml();
        $yml->yml('utf-8');
        $yml->setShop(Configuration::get('PS_SHOP_NAME'), Configuration::get('YA_MARKET_NAME'), $shop_url);
        if (Configuration::get('YA_MARKET_SET_ALLCURRENCY')) {
            foreach ($currencies as $currency) {
                $yml->addCurrency(
                    $currency['iso_code'],
                    ((float)$currency_default->conversion_rate/(float)$currency['conversion_rate'])
                );
            }
            unset($currencies);
        } else {
            $yml->addCurrency($currency_default->iso_code, (float)$currency_default->conversion_rate);
        }

        foreach ($categories as $category) {
            if (!in_array($category['id_category'], $cats) || $category['id_category'] == 1) {
                continue;
            }

            if (Configuration::get('YA_MARKET_SET_NACTIVECAT')) {
                if (!$category['active']) {
                    continue;
                }
            }

            if (Configuration::get('YA_MARKET_CATALL')) {
                if (in_array($category['id_category'], $cats)) {
                    $yml->addCategory($category['name'], $category['id_category'], $category['id_parent']);
                }
            } else {
                $yml->addCategory($category['name'], $category['id_category'], $category['id_parent']);
            }
        }

        foreach ($yml->categories as $cat) {
            $category_object = new Category($cat['id']);
            $products = $category_object->getProducts($id_lang, 1, 10000);
            if ($products) {
                foreach ($products as $product) {
                    if ($product['id_category_default'] != $cat['id']) {
                        continue;
                    }

                    $data = array();
                    if ($yamarket_set_combinations && !Configuration::get('YA_MARKET_SHORT')) {
                        $product_object = new Product($product['id_product'], false, $id_lang);
                        $combinations = $product_object->getAttributeCombinations($id_lang);
                    } else {
                        $combinations = false;
                    }

                    if (is_array($combinations) && count($combinations) > 0) {
                        $comb_array = array();
                        foreach ($combinations as $combination) {
                            $comb_array[$combination['id_product_attribute']]['id_product_attribute']
                                = $combination['id_product_attribute'];
                            $comb_array[$combination['id_product_attribute']]['price'] = Product::getPriceStatic(
                                $product['id_product'],
                                true,
                                $combination['id_product_attribute']
                            );

                            $comb_array[$combination['id_product_attribute']]['reference'] = $combination['reference'];
                            $comb_array[$combination['id_product_attribute']]['ean13'] = $combination['ean13'];
                            $comb_array[$combination['id_product_attribute']]['quantity'] = $combination['quantity'];
                            $comb_array[$combination['id_product_attribute']]['minimal_quantity']
                                = $combination['minimal_quantity'];
                            $comb_array[$combination['id_product_attribute']]['weight'] = $combination['weight'];
                            $comb_array[$combination['id_product_attribute']]['attributes'][$combination['group_name']]
                                = $combination['attribute_name'];
                            if (!isset($comb_array[$combination['id_product_attribute']]['comb_url'])) {
                                $comb_array[$combination['id_product_attribute']]['comb_url'] = '';
                            }
                            $comb_array[$combination['id_product_attribute']]['comb_url'] .= '/'.
                                Tools::str2url(
                                    $combination['group_name']
                                ).'-'.str_replace(
                                    Configuration::get('PS_ATTRIBUTE_ANCHOR_SEPARATOR'),
                                    '_',
                                    Tools::str2url(
                                        str_replace(
                                            array(',', '.'),
                                            '-',
                                            $combination['attribute_name']
                                        )
                                    )
                                );
                        }

                        foreach ($comb_array as $combination) {
                            $data = $this->makeData($product, $combination);
                            $available = $data['available'];
                            unset($data['available']);
                            if (!empty($data) && $data['price'] != 0) {
                                $yml->addOffer($data['id'], $data, $available);
                            }
                        }
                    } else {
                        $data = $this->makeData($product);
                        $available = $data['available'];
                        unset($data['available']);
                        if (!empty($data) && (int)$data['price'] != 0) {
                            $yml->addOffer($data['id'], $data, $available);
                        }
                    }

                    unset($data);
                }
            }

            unset($product);
        }

        unset($categories);
        $xml = $yml->getXml();
        if ($cron) {
            if ($fp = fopen(_PS_UPLOAD_DIR_.'yml.'.$this->context->shop->id.'.xml'.($this->gzip ? '.gz' : ''), 'w')) {
                fwrite($fp, $xml);
                fclose($fp);
                $this->logSave('market_generate: Cron '.$this->l('Generate price'));
            }
        } else {
            if ($this->gzip) {
                header('Content-type:application/x-gzip');
                header('Content-Disposition: attachment; filename=yml.'.$this->context->shop->id.'.xml.gz');
                $this->logSave('market_generate: gzip view '.$this->l('Generate price'));
            } else {
                header('Content-type:application/xml;  charset=windows-1251');
            }
            $this->logSave('market_generate: view '.$this->l('Generate price'));
            echo $xml;
            exit;
        }
    }

    public function selfPostProcess()
    {
        $error = Tools::getValue('error');
        if (!empty($error)) {
            $this->metrika_error = $this->displayError($this->cryptor->decrypt($error));
        }

        if (Tools::getIsset('generatemanual')) {
            $this->generateXML(false);
        }

        if (Tools::isSubmit('output_csr')) {
            Mws::outputCsr();
        }

        if (Tools::isSubmit('generate_cert')) {
            Mws::generateCsr();
        }

        if (Tools::isSubmit('cert_upload')) {
            Mws::upload();
        }

        if (Tools::isSubmit('submitmetrikaModule')) {
            $this->metrika_status = $this->validateMetrika();
            if ($this->metrika_valid && Configuration::get('YA_METRIKA_ACTIVE')) {
                $this->sendMetrikaData();
            } elseif ($this->metrika_valid && !Configuration::get('YA_METRIKA_ACTIVE')) {
                $this->metrika_status .= $this->displayError(
                    $this->l(
                        'The changes have saved but not sent! Turn On The Metric!'
                    )
                );
            }
            $this->update_status = $this->sendStatistics();
        } elseif (Tools::isSubmit('submitorgModule')) {
            $this->org_status = $this->validateKassa();
            $this->update_status = $this->sendStatistics();//$this->sendStatistics();
        } elseif (Tools::isSubmit('submitPokupkiModule')) {
            $this->pokupki_status = $this->validatePokupki();
            $this->update_status = $this->sendStatistics();//$this->sendStatistics();
        } elseif (Tools::isSubmit('submitp2pModule')) {
            $this->p2p_status = $this->validateP2P();
            $this->update_status = $this->sendStatistics();//$this->sendStatistics();
        } elseif (Tools::isSubmit('submitmarketModule')) {
            $this->market_status = $this->validateMarket();
            $this->update_status = $this->sendStatistics();//$this->sendStatistics();
        }
    }

    public function sendStatistics()
    {
        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';

        $array = array(
            'url' => Tools::getShopDomainSsl(true),
            'cms' => 'prestashop',
            'version' => _PS_VERSION_,
            'ver_mod' => $this->version,
            'email' => $this->context->employee->email,
            'shopid' => Configuration::get('YA_ORG_SHOPID'),
            'settings' => array(
                'kassa' => (bool) Configuration::get('YA_ORG_ACTIVE'),
                'p2p' => (bool) Configuration::get('YA_P2P_ACTIVE'),
                'metrika' =>(bool) Configuration::get('YA_METRIKA_ACTIVE')
            )
        );

        $array_crypt = base64_encode(serialize($array));

        $url = 'https://statcms.yamoney.ru/v2/';
        $curlOpt = array(
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_POST => true,
        );

        $curlOpt[CURLOPT_HTTPHEADER] = $headers;
        $curlOpt[CURLOPT_POSTFIELDS] = http_build_query(array('data' => $array_crypt, 'lbl'=>0));

        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOpt);
        curl_exec($curl);
        //$rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        /*
          $json=json_decode($rbody);
            if ($rcode==200 && isset($json->new_version)){
                return $json->new_version;
            }else{*/
        return false;
        //	}
    }

    public function sendMetrikaData()
    {
        $m = new Metrika();
        $response = $m->run();
        $data = array(
            'YA_METRIKA_CART' =>  array(
                'name' => 'YA_METRIKA_CART',
                'flag' => 'basket',
                'type' => 'action',
                'class' => 1,
                'depth' => 0,
                'conditions' => array(
                    array(
                        'url' => 'metrikaCart',
                        'type' => 'exact'
                    )
                )

            ),
            'YA_METRIKA_ORDER' => array(
                'name' => 'YA_METRIKA_ORDER',
                'flag' => 'order',
                'type' => 'action',
                'class' => 1,
                'depth' => 0,
                'conditions' => array(
                    array(
                        'url' => 'metrikaOrder',
                        'type' => 'exact'
                    )
                )

            ),
            'YA_METRIKA_WISHLIST' => array(
                'name' => 'YA_METRIKA_WISHLIST',
                'flag' => '',
                'type' => 'action',
                'class' => 1,
                'depth' => 0,
                'conditions' => array(
                    array(
                        'url' => 'metrikaWishlist',
                        'type' => 'exact'
                    )
                )

            ),
        );

        $ret = array();
        $error = '';
        if (Configuration::get('YA_METRIKA_TOKEN') != '') {
            if ($response) {
                $counter = $m->getCounter();
                if (!empty($counter->counter->code)) {
                    Configuration::UpdateValue('YA_METRIKA_CODE', $counter->counter->code, true);
                }
                $otvet = $m->editCounter();
                if ($otvet->counter->id != Configuration::get('YA_METRIKA_NUMBER')) {
                    $error .= $this->displayError(
                        $this->l(
                            'Saving the settings the meter is not the meter number is incorrect.'
                        )
                    );
                } else {
                    $tmp_goals = $m->getCounterGoals();
                    $goals = array();
                    foreach ($tmp_goals->goals as $goal) {
                        $goals[$goal->name] = $goal;
                    }

                    $types = array('YA_METRIKA_ORDER', 'YA_METRIKA_WISHLIST', 'YA_METRIKA_CART');
                    foreach ($types as $type) {
                        $conf = explode('_', $type);
                        $conf = $conf[0].'_'.$conf[1].'_CELI_'.$conf[2];
                        if (Configuration::get($conf) == 0 && isset($goals[$type])) {
                            $ret['delete_'.$type] = $m->deleteCounterGoal($goals[$type]->id);
                        } elseif (Configuration::get($conf) == 1 && !isset($goals[$type])) {
                            $params = $data[$type];
                            $ret['add_'.$type] = $m->addCounterGoal(array('goal' => $params));
                        }
                    }
                }
            } elseif (!empty($m->errors)) {
                $error .= $this->displayError($m->errors);
            }
        } else {
            $error .= $this->displayError(
                $this->l(
                    'The token for authorization is missing! Get the token and repeat!'
                )
            );
        }

        if ($error == '') {
            $this->metrika_status .= $this->displayConfirmation(
                $this->l(
                    'Data was successfully sent and saved! Code metrici updated pages automatically.'
                )
            );
        } else {
            $this->metrika_status .= $error;
        }
    }

    public function validateMetrika()
    {
        $this->metrika_valid = false;
        $errors = '';
        Configuration::UpdateValue('YA_METRIKA_SET_WEBVIZOR', Tools::getValue('YA_METRIKA_SET_WEBVIZOR'));
        Configuration::UpdateValue('YA_METRIKA_SET_CLICKMAP', Tools::getValue('YA_METRIKA_SET_CLICKMAP'));
        Configuration::UpdateValue('YA_METRIKA_SET_OUTLINK', Tools::getValue('YA_METRIKA_SET_OUTLINK'));
        Configuration::UpdateValue('YA_METRIKA_SET_OTKAZI', Tools::getValue('YA_METRIKA_SET_OTKAZI'));
        Configuration::UpdateValue('YA_METRIKA_SET_HASH', Tools::getValue('YA_METRIKA_SET_HASH'));
        Configuration::UpdateValue('YA_METRIKA_CELI_CART', Tools::getValue('YA_METRIKA_CELI_CART'));
        Configuration::UpdateValue('YA_METRIKA_CELI_ORDER', Tools::getValue('YA_METRIKA_CELI_ORDER'));
        Configuration::UpdateValue('YA_METRIKA_CELI_WISHLIST', Tools::getValue('YA_METRIKA_CELI_WISHLIST'));
        Configuration::UpdateValue('YA_METRIKA_ACTIVE', Tools::getValue('YA_METRIKA_ACTIVE'));

        if (Tools::getValue('YA_METRIKA_ID_APPLICATION') == '') {
            $errors .= $this->displayError($this->l('Not filled in the application ID!'));
        } else {
            Configuration::UpdateValue('YA_METRIKA_ID_APPLICATION', Tools::getValue('YA_METRIKA_ID_APPLICATION'));
        }

        if (Tools::getValue('YA_METRIKA_PASSWORD_APPLICATION') == '') {
            $errors .= $this->displayError($this->l('Not filled with an application-specific Password!'));
        } else {
            Configuration::UpdateValue(
                'YA_METRIKA_PASSWORD_APPLICATION',
                Tools::getValue('YA_METRIKA_PASSWORD_APPLICATION')
            );
        }

        if (Tools::getValue('YA_METRIKA_NUMBER') == '') {
            $errors .= $this->displayError($this->l('Not filled the room counter Medici!'));
        } else {
            Configuration::UpdateValue('YA_METRIKA_NUMBER', Tools::getValue('YA_METRIKA_NUMBER'));
        }

        if ($errors == '') {
            $errors = $this->displayConfirmation($this->l('Settings saved successfully!'));
            $this->metrika_valid = true;
        }

        return $errors;
    }

    public function validatePokupki()
    {
        $array_c = array();
        $errors = '';
        foreach ($_POST as $k => $post) {
            if (strpos($k, 'YA_POKUPKI_DELIVERY_') !== false) {
                $id = str_replace('YA_POKUPKI_DELIVERY_', '', $k);
                $array_c[$id] = $post;
            }
        }

        Configuration::UpdateValue('YA_POKUPKI_CARRIER_SERIALIZE', serialize($array_c));
        Configuration::UpdateValue(
            'YA_POKUPKI_PREDOPLATA_YANDEX',
            Tools::getValue('YA_POKUPKI_PREDOPLATA_YANDEX')
        );
        Configuration::UpdateValue(
            'YA_POKUPKI_PREDOPLATA_SHOP_PREPAID',
            Tools::getValue('YA_POKUPKI_PREDOPLATA_SHOP_PREPAID')
        );
        Configuration::UpdateValue(
            'YA_POKUPKI_POSTOPLATA_CASH_ON_DELIVERY',
            Tools::getValue('YA_POKUPKI_POSTOPLATA_CASH_ON_DELIVERY')
        );
        Configuration::UpdateValue(
            'YA_POKUPKI_POSTOPLATA_CARD_ON_DELIVERY',
            Tools::getValue('YA_POKUPKI_POSTOPLATA_CARD_ON_DELIVERY')
        );
        Configuration::UpdateValue('YA_POKUPKI_SET_CHANGEC', Tools::getValue('YA_POKUPKI_SET_CHANGEC'));
        Configuration::UpdateValue('YA_POKUPKI_PUNKT', Tools::getValue('YA_POKUPKI_PUNKT'));

        if (Tools::getValue('YA_POKUPKI_TOKEN') == '') {
            $errors .= $this->displayError($this->l('Token to refer to the Yandex store, not filled!'));
        } else {
            Configuration::UpdateValue('YA_POKUPKI_TOKEN', Tools::getValue('YA_POKUPKI_TOKEN'));
        }

        if (Tools::getValue('YA_POKUPKI_APIURL') == '') {
            $errors .= $this->displayError($this->l('The API URL is not filled in!'));
        } else {
            Configuration::UpdateValue('YA_POKUPKI_APIURL', Tools::getValue('YA_POKUPKI_APIURL'));
        }

        if (Tools::getValue('YA_POKUPKI_LOGIN') == '') {
            $errors .= $this->displayError($this->l('Fill your username in Yandex!'));
        } else {
            Configuration::UpdateValue('YA_POKUPKI_LOGIN', Tools::getValue('YA_POKUPKI_LOGIN'));
        }

        if (Tools::getValue('YA_POKUPKI_NC') == '') {
            $errors .= $this->displayError($this->l('Fill your room campaign!'));
        } else {
            Configuration::UpdateValue('YA_POKUPKI_NC', Tools::getValue('YA_POKUPKI_NC'));
        }

        if (Tools::getValue('YA_POKUPKI_ID') == '') {
            $errors .= $this->displayError($this->l('Not filled in the application ID!'));
        } else {
            Configuration::UpdateValue('YA_POKUPKI_ID', Tools::getValue('YA_POKUPKI_ID'));
        }

        if (Tools::getValue('YA_POKUPKI_PW') == '') {
            $errors .= $this->displayError($this->l('Not filled with an application-specific Password!'));
        } else {
            Configuration::UpdateValue('YA_POKUPKI_PW', Tools::getValue('YA_POKUPKI_PW'));
        }

        if ($errors == '') {
            $carriers = Carrier::getCarriers(Context::getContext()->language->id, true, false, false, null, 5);
            foreach ($carriers as $a) {
                Configuration::UpdateValue(
                    'YA_POKUPKI_DELIVERY_'.$a['id_carrier'],
                    Tools::getValue('YA_POKUPKI_DELIVERY_'.$a['id_carrier'])
                );
            }

            $errors = $this->displayConfirmation($this->l('Settings saved successfully!'));
        }

        return $errors;
    }

    public function validateMarket()
    {
        $errors = '';
        Configuration::UpdateValue('YA_MARKET_SHORT', Tools::getValue('YA_MARKET_SHORT'));
        Configuration::UpdateValue('YA_MARKET_SET_ALLCURRENCY', Tools::getValue('YA_MARKET_SET_ALLCURRENCY'));
        Configuration::UpdateValue('YA_MARKET_DESC_TYPE', Tools::getValue('YA_MARKET_DESC_TYPE'));
        Configuration::UpdateValue('YA_MARKET_DOSTUPNOST', Tools::getValue('YA_MARKET_DOSTUPNOST'));
        Configuration::UpdateValue('YA_MARKET_SET_GZIP', Tools::getValue('YA_MARKET_SET_GZIP'));
        Configuration::UpdateValue('YA_MARKET_SET_AVAILABLE', Tools::getValue('YA_MARKET_SET_AVAILABLE'));
        Configuration::UpdateValue('YA_MARKET_SET_NACTIVECAT', Tools::getValue('YA_MARKET_SET_NACTIVECAT'));
        Configuration::UpdateValue('YA_MARKET_SET_HOMECARRIER', Tools::getValue('YA_MARKET_SET_HOMECARRIER'));
        Configuration::UpdateValue('YA_MARKET_SET_COMBINATIONS', Tools::getValue('YA_MARKET_SET_COMBINATIONS'));
        Configuration::UpdateValue('YA_MARKET_SET_DIMENSIONS', Tools::getValue('YA_MARKET_SET_DIMENSIONS'));
        Configuration::UpdateValue('YA_MARKET_SET_SAMOVIVOZ', Tools::getValue('YA_MARKET_SET_SAMOVIVOZ'));
        Configuration::UpdateValue('YA_MARKET_SET_DOST', Tools::getValue('YA_MARKET_SET_DOST'));
        Configuration::UpdateValue('YA_MARKET_SET_ROZNICA', Tools::getValue('YA_MARKET_SET_ROZNICA'));
        Configuration::UpdateValue('YA_MARKET_MK', Tools::getValue('YA_MARKET_MK'));
        Configuration::UpdateValue('YA_MARKET_HKP', Tools::getValue('YA_MARKET_HKP'));
        Configuration::UpdateValue('YA_MARKET_CATEGORIES', serialize(Tools::getValue('YA_MARKET_CATEGORIES')));

        if (Tools::getValue('YA_MARKET_NAME') == '') {
            $errors .= $this->displayError($this->l('The company name is not filled in!'));
        } else {
            Configuration::UpdateValue('YA_MARKET_NAME', Tools::getValue('YA_MARKET_NAME'));
        }

        if (Tools::getValue('YA_MARKET_DELIVERY') == '') {
            $errors .= $this->displayError($this->l('The shipping cost to your home location is not filled in!'));
        } else {
            Configuration::UpdateValue('YA_MARKET_DELIVERY', Tools::getValue('YA_MARKET_DELIVERY'));
        }

        if ($errors == '') {
            $errors = $this->displayConfirmation($this->l('Settings saved successfully!'));
        }

        return $errors;
    }

    public function validateKassa()
    {
        $errors = '';
        Configuration::UpdateValue('YA_ORG_MIN', Tools::getValue('YA_ORG_MIN'));
        Configuration::UpdateValue('YA_ORG_PAYMENT_YANDEX', Tools::getValue('YA_ORG_PAYMENT_YANDEX'));
        Configuration::UpdateValue('YA_ORG_PAYMENT_CARD', Tools::getValue('YA_ORG_PAYMENT_CARD'));
        Configuration::UpdateValue('YA_ORG_PAYMENT_MOBILE', Tools::getValue('YA_ORG_PAYMENT_MOBILE'));
        Configuration::UpdateValue('YA_ORG_PAYMENT_WEBMONEY', Tools::getValue('YA_ORG_PAYMENT_WEBMONEY'));
        Configuration::UpdateValue('YA_ORG_PAYMENT_TERMINAL', Tools::getValue('YA_ORG_PAYMENT_TERMINAL'));
        Configuration::UpdateValue('YA_ORG_PAYMENT_SBER', Tools::getValue('YA_ORG_PAYMENT_SBER'));
        Configuration::UpdateValue('YA_ORG_PAYMENT_ALFA', Tools::getValue('YA_ORG_PAYMENT_ALFA'));
        Configuration::UpdateValue('YA_ORG_PAYMENT_PB', Tools::getValue('YA_ORG_PAYMENT_PB'));
        Configuration::UpdateValue('YA_ORG_PAYMENT_MA', Tools::getValue('YA_ORG_PAYMENT_MA'));
        Configuration::UpdateValue('YA_ORG_PAYMENT_QW', Tools::getValue('YA_ORG_PAYMENT_QW'));
        Configuration::UpdateValue('YA_ORG_PAYMENT_QP', Tools::getValue('YA_ORG_PAYMENT_QP'));
        Configuration::UpdateValue('YA_ORG_TYPE', Tools::getValue('YA_ORG_TYPE'));
        Configuration::UpdateValue('YA_ORG_LOGGING_ON', Tools::getValue('YA_ORG_LOGGING_ON'));
        Configuration::UpdateValue('YA_ORG_PAYLOGO_ON', Tools::getValue('YA_ORG_PAYLOGO_ON'));
        Configuration::UpdateValue('YA_ORG_ACTIVE', Tools::getValue('YA_ORG_ACTIVE'));
        Configuration::UpdateValue('YA_ORG_INSIDE', Tools::getValue('YA_ORG_INSIDE'));

        if (Tools::getValue('YA_ORG_ACTIVE') && Configuration::get('yamodule_mws_csr_sign')) {
            Mws::generateCsr();
        }

        if (Tools::getValue('YA_ORG_SHOPID') == '') {
            $errors .= $this->displayError($this->l('ShopId not filled!'));
        } else {
            Configuration::UpdateValue('YA_ORG_SHOPID', trim(Tools::getValue('YA_ORG_SHOPID')));
        }

        if (Tools::getValue('YA_ORG_SCID') == '') {
            $errors .= $this->displayError($this->l('SCID is not filled!'));
        } else {
            Configuration::UpdateValue('YA_ORG_SCID', trim(Tools::getValue('YA_ORG_SCID')));
        }

        if (Tools::getValue('YA_ORG_MD5_PASSWORD') == '') {
            $errors .= $this->displayError($this->l('The password is not filled!'));
        } else {
            Configuration::UpdateValue('YA_ORG_MD5_PASSWORD', trim(Tools::getValue('YA_ORG_MD5_PASSWORD')));
        }

        if ($errors == '') {
            $errors = $this->displayConfirmation($this->l('Settings saved successfully!'));
        }

        return $errors;
    }

    public function validateP2P()
    {
        $errors = '';
        Configuration::UpdateValue('YA_P2P_MIN', Tools::getValue('YA_P2P_MIN'));
        Configuration::UpdateValue('YA_P2P_ACTIVE', Tools::getValue('YA_P2P_ACTIVE'));
        Configuration::UpdateValue('YA_P2P_LOGGING_ON', Tools::getValue('YA_P2P_LOGGING_ON'));

        if (Tools::getValue('YA_P2P_NUMBER') == '') {
            $errors .= $this->displayError($this->l('Account number is not filled in!'));
        } else {
            Configuration::UpdateValue('YA_P2P_NUMBER', Tools::getValue('YA_P2P_NUMBER'));
        }

        if (Tools::getValue('YA_P2P_IDENTIFICATOR') == '') {
            $errors .= $this->displayError($this->l('The application ID is not filled in!'));
        } else {
            Configuration::UpdateValue('YA_P2P_IDENTIFICATOR', trim(Tools::getValue('YA_P2P_IDENTIFICATOR')));
        }

        if (Tools::getValue('YA_P2P_KEY') == '') {
            $errors .= $this->displayError($this->l('O2Auth key is not filled!'));
        } else {
            Configuration::UpdateValue('YA_P2P_KEY', trim(Tools::getValue('YA_P2P_KEY')));
        }

        if ($errors == '') {
            $errors = $this->displayConfirmation($this->l('Settings saved successfully!'));
        }

        return $errors;
    }

    public function getContent()
    {
        $this->context->controller->addJS($this->_path.'/views/js/main.js');
        $this->context->controller->addJS($this->_path.'/views/js/jquery.total-storage.js');
        $this->context->controller->addCSS($this->_path.'/views/css/admin.css');
        $this->selfPostProcess();
        $this->context->controller->addJqueryUI('ui.tabs');
        $vars_p2p = Configuration::getMultiple(array(
            'YA_P2P_IDENTIFICATOR',
            'YA_P2P_NUMBER',
            'YA_P2P_MIN',
            'YA_P2P_ACTIVE',
            'YA_P2P_KEY',
            'YA_P2P_LOGGING_ON',
            'YA_P2P_SECRET'
        ));
        $vars_org = Configuration::getMultiple(array(
            'YA_ORG_SHOPID',
            'YA_ORG_SCID',
            'YA_ORG_ACTIVE',
            'YA_ORG_MD5_PASSWORD',
            'YA_ORG_MIN',
            'YA_ORG_TYPE',
            'YA_ORG_INSIDE',
            'YA_ORG_PAYLOGO_ON',
            'YA_ORG_LOGGING_ON',
            'YA_ORG_PAYMENT_YANDEX',
            'YA_ORG_PAYMENT_CARD',
            'YA_ORG_PAYMENT_MOBILE',
            'YA_ORG_PAYMENT_WEBMONEY',
            'YA_ORG_PAYMENT_TERMINAL',
            'YA_ORG_PAYMENT_SBER',
            'YA_ORG_PAYMENT_PB',
            'YA_ORG_PAYMENT_MA',
            'YA_ORG_PAYMENT_QW',
            'YA_ORG_PAYMENT_QP',
            'YA_ORG_PAYMENT_ALFA'
        ));
        $vars_metrika = Configuration::getMultiple(array(
            'YA_METRIKA_PASSWORD_APPLICATION',
            'YA_METRIKA_ID_APPLICATION',
            'YA_METRIKA_SET_WEBVIZOR',
            'YA_METRIKA_SET_CLICKMAP',
            'YA_METRIKA_SET_OUTLINK',
            'YA_METRIKA_SET_OTKAZI',
            'YA_METRIKA_SET_HASH',
            'YA_METRIKA_ACTIVE',
            'YA_METRIKA_TOKEN',
            'YA_METRIKA_NUMBER',
            'YA_METRIKA_CELI_CART',
            'YA_METRIKA_CELI_ORDER',
            'YA_METRIKA_CELI_WISHLIST'
        ));
        $vars_pokupki = Configuration::getMultiple(array(
            'YA_POKUPKI_PUNKT',
            'YA_POKUPKI_TOKEN',
            'YA_POKUPKI_PREDOPLATA_YANDEX',
            'YA_POKUPKI_PREDOPLATA_SHOP_PREPAID',
            'YA_POKUPKI_POSTOPLATA_CASH_ON_DELIVERY',
            'YA_POKUPKI_POSTOPLATA_CARD_ON_DELIVERY',
            'YA_POKUPKI_APIURL',
            'YA_POKUPKI_SET_CHANGEC',
            'YA_POKUPKI_NC',
            'YA_POKUPKI_LOGIN',
            'YA_POKUPKI_ID',
            'YA_POKUPKI_PW',
            'YA_POKUPKI_YATOKEN',
        ));
        $vars_market = Configuration::getMultiple(array(
            'YA_MARKET_SET_ALLCURRENCY',
            'YA_MARKET_NAME',
            'YA_MARKET_SET_AVAILABLE',
            'YA_MARKET_SET_NACTIVECAT',
            'YA_MARKET_SET_HOMECARRIER',
            'YA_MARKET_SET_COMBINATIONS',
            'YA_MARKET_CATALL',
            'YA_MARKET_SET_DIMENSIONS',
            'YA_MARKET_SET_SAMOVIVOZ',
            'YA_MARKET_SET_DOST',
            'YA_MARKET_SET_ROZNICA',
            'YA_MARKET_DELIVERY',
            'YA_MARKET_MK',
            'YA_MARKET_SHORT',
            'YA_MARKET_HKP',
            'YA_MARKET_DOSTUPNOST',
            'YA_MARKET_SET_GZIP',
            'YA_MARKET_DESC_TYPE',
        ));

        $cats = array();
        if ($c = Configuration::get('YA_MARKET_CATEGORIES')) {
            $uc = unserialize($c);
            if (is_array($uc)) {
                $cats = $uc;
            }
        }

        $hforms = new hforms();
        $hforms->cats = $cats;

        $vars_pokupki['YA_POKUPKI_FD'] = 'JSON';
        $vars_pokupki['YA_POKUPKI_TA'] = 'URL';
        $vars_org['YA_ORG_TEXT_INSIDE'] = "Shop ID, scid, ShopPassword можно посмотреть".
            " в <a href='https://money.yandex.ru/joinups' target='_blank'>личном кабинете</a>".
            " после подключения Яндекс.Кассы.";
        $vars_p2p['YA_P2P_TEXT_INSIDE'] = "ID и секретное слово вы получите после".
            " <a href='https://sp-money.yandex.ru/myservices/new.xml'".
            " target='_blank'>регистрации приложения</a>".
            " на сайте Яндекс.Денег";
        $this->context->smarty->assign(array(
            'ya_version' => $this->version,
            'YA_ORG_ACTIVE' => $vars_org['YA_ORG_ACTIVE'],
            'YA_ORG_SHOPID' => $vars_org['YA_ORG_SHOPID'],
            'orders_link' => $this->context->link->getAdminLink('AdminOrders', false)
                .'&token='.Tools::getAdminTokenLite('AdminOrders'),
            'ajax_limk_ym' => $this->context->link->getAdminLink('AdminModules', false)
                .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='
                .$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
            'mws_cn' => '/business/ps/yacms-'.$vars_org['YA_ORG_SHOPID'],
            'mws_sign' => Configuration::get('yamodule_mws_csr_sign'),
            'mws_cert' => Configuration::get('yamodule_mws_cert') ? true : false,
            'this_path' => $this->_path,
            'update_status' => $this->update_status,
            'metrika_status' => $this->metrika_status,
            'market_status' => $this->market_status,
            'pokupki_status' => $this->pokupki_status,
            'p2p_status' => $this->p2p_status,
            'org_status' => $this->org_status,
            'mws_status' => $this->mws_status,
            'money_p2p' => $this->renderForm('p2p', $vars_p2p, $hforms->getFormYamoney()),
            'money_org' => $this->renderForm('org', $vars_org, $hforms->getFormYamoneyOrg()),
            'money_metrika' => $this->renderForm('metrika', $vars_metrika, $hforms->getFormYamoneyMetrika()),
            'money_market' => $this->renderForm('market', $vars_market, $hforms->getFormYamoneyMarket()),
            'money_marketp' => $this->renderForm('Pokupki', $vars_pokupki, $hforms->getFormYaPokupki()),
        ));
        return $this->display(__FILE__, 'admin.tpl');
    }

    public static function validateResponse(
        $message = '',
        $code = 0,
        $action = '',
        $shopId = 0,
        $invoiceId = 0,
        $toYandex = false
    ) {
        if ($message != '') {
            self::logSave('yamodule: validate response '.$message);
        }

        if ($toYandex) {
            ob_start();
            ob_clean();
            header("Content-type: text/xml; charset=utf-8");
            $output = '<?xml version="1.0" encoding="UTF-8"?> ';
            $output .= '<'.$action.'Response performedDatetime="'.date(DATE_ATOM).'" ';
            $output .= 'code="'.$code.'" ';
            $output .= 'invoiceId="'.$invoiceId.'" ';
            $output .= 'shopId="'.$shopId.'" ';
            $output .= 'message="'.$message.'"/>';
            echo $output;
            ob_end_flush();
            die();
        }
    }

    public function logSave($logtext)
    {
        $logdir = 'log_files';
        $real_log_dir = _PS_MODULE_DIR_.'/yamodule/'.$logdir;
        if (!is_dir($real_log_dir)) {
            mkdir($real_log_dir, 0777);
        } else {
            chmod($real_log_dir, 0777);
        }

        $real_log_file = $real_log_dir.'/'.date('Y-m-d').'.log';
        $h = fopen($real_log_file, 'ab');
        fwrite($h, date('Y-m-d H:i:s ') . '[' . addslashes($_SERVER['REMOTE_ADDR']) . '] ' . $logtext . "\n");
        fclose($h);
    }

    public function settingsPaymentOptions($type)
    {
        $tp = array(
            'PC' => 'Оплата из кошелька в Яндекс.Деньгах',
            'AC' => 'Оплата с банковской карты',
            'GP' => 'Оплата наличными через кассы и терминалы',
            'MC' => 'Оплата со счета мобильного телефона',
            'WM' => 'Оплата в системе WebMoney',
            'SB' => 'Оплата по SMS или Сбербанк Онлайн',
            'AB' => 'Оплата через Альфа-Клик',
            'MC' => 'Платеж со счета мобильного телефона',
            'MA' => 'Оплата через MasterPass',
            'PB' => 'Оплата через Промсвязьбанк',
            'QW' => 'Оплата через QIWI Wallet',
            'QP' => 'Оплата через доверительный платеж (Куппи.ру)',
        );

        return isset($tp[$type]) ? $tp[$type] : $type;
    }

    public function hookdisplayPayment($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $cart = $this->context->cart;
        $total_to_pay = $cart->getOrderTotal(true);
        $rub_currency_id = Currency::getIdByIsoCode('RUB');
        if ($cart->id_currency != $rub_currency_id) {
            $from_currency = new Currency($cart->id_curre1ncy);
            $to_currency = new Currency($rub_currency_id);
            $total_to_pay = Tools::convertPriceFull($total_to_pay, $from_currency, $to_currency);
        }

        $this->context->smarty->assign(array(
            'summ' => number_format($total_to_pay, 2, '.', ''),
            'this_path' => $this->_path,
            'this_path_ssl' => Tools::getHttpHost(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
        ));

        $display = '';
        if (Configuration::get('YA_P2P_ACTIVE')) {
            $vars_p2p = Configuration::getMultiple(array(
                'YA_P2P_NUMBER',
                'YA_P2P_ACTIVE',
                'YA_P2P_MIN',
            ));

            $this->context->smarty->assign(array(
                'DATA_P2P' => $vars_p2p,
                'price' => number_format($total_to_pay, 2, '.', ''),
                'cart' => $this->context->cart
            ));

            $display .= $this->display(__FILE__, 'payment.tpl');
        }

        if (Configuration::get('YA_ORG_ACTIVE')) {
            $vars_org = Configuration::getMultiple(array(
                'YA_ORG_SHOPID',
                'YA_ORG_SCID',
                'YA_ORG_ACTIVE',
                'YA_ORG_TYPE',
                'YA_ORG_MIN'
            ));

            $this->context->smarty->assign(array(
                'DATA_ORG' => $vars_org,
                'yandex_logo' => Configuration::get('YA_ORG_PAYLOGO_ON'),
                'id_cart' => $params['cart']->id,
                'customer' => new Customer($params['cart']->id_customer),
                'address' => new Address($this->context->cart->id_address_delivery),
                'total_to_pay' => number_format($total_to_pay, 2, '.', ''),
                'this_path_ssl' => Tools::getShopDomainSsl(true, true)
                    . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
                'shop_name' => Configuration::get('PS_SHOP_NAME')
            ));

            $payments = Configuration::getMultiple(array(
                'YA_ORG_PAYMENT_YANDEX',
                'YA_ORG_PAYMENT_CARD',
                'YA_ORG_PAYMENT_MOBILE',
                'YA_ORG_PAYMENT_WEBMONEY',
                'YA_ORG_PAYMENT_TERMINAL',
                'YA_ORG_PAYMENT_SBER',
                'YA_ORG_PAYMENT_PB',
                'YA_ORG_PAYMENT_MA',
                'YA_ORG_PAYMENT_QW',
                'YA_ORG_PAYMENT_QP',
                'YA_ORG_PAYMENT_ALFA'
            ));

            if (Configuration::get('YA_ORG_INSIDE')) {
                if ($payments['YA_ORG_PAYMENT_YANDEX']) {
                    $this->smarty->assign(array(
                        'pt' => 'PC',
                        'buttontext' => $this->l('Payment from the purse in Yandex.Money.')
                    ));

                    $display .= $this->display(__FILE__, 'kassa.tpl');
                }

                if ($payments['YA_ORG_PAYMENT_CARD']) {
                    $this->smarty->assign(array(
                        'pt' => 'AC',
                        'buttontext' => $this->l('Arbitrary payment with Bank card.')
                    ));

                    $display .= $this->display(__FILE__, 'kassa.tpl');
                }

                if ($payments['YA_ORG_PAYMENT_MOBILE']) {
                    $this->smarty->assign(array(
                        'pt' => 'MC',
                        'buttontext' => $this->l('Payment with mobile phone account.')
                    ));

                    $display .= $this->display(__FILE__, 'kassa.tpl');
                }

                if ($payments['YA_ORG_PAYMENT_WEBMONEY']) {
                    $this->smarty->assign(array(
                        'pt' => 'WM',
                        'buttontext' => $this->l('Payment of the purse in system WebMoney.')
                    ));

                    $display .= $this->display(__FILE__, 'kassa.tpl');
                }

                if ($payments['YA_ORG_PAYMENT_TERMINAL']) {
                    $this->smarty->assign(array(
                        'pt' => 'GP',
                        'buttontext' => $this->l('Payment in cash through cash desks and terminals.')
                    ));

                    $display .= $this->display(__FILE__, 'kassa.tpl');
                }

                if ($payments['YA_ORG_PAYMENT_SBER']) {
                    $this->smarty->assign(array(
                        'pt' => 'SB',
                        'buttontext' => $this->l('Payment via Sberbank: payment by SMS or Sberbank Online.')
                    ));

                    $display .= $this->display(__FILE__, 'kassa.tpl');
                }
                if ($payments['YA_ORG_PAYMENT_ALFA']) {
                    $this->smarty->assign(array(
                        'pt' => 'AB',
                        'buttontext' => $this->l('Payment via Alfa-Click.')
                    ));

                    $display .= $this->display(__FILE__, 'kassa.tpl');
                }
                if ($payments['YA_ORG_PAYMENT_PB']) {
                    $this->smarty->assign(array(
                        'pt' => 'PB',
                        'buttontext' => $this->l('Payments via Promsvyazbank.')
                    ));

                    $display .= $this->display(__FILE__, 'kassa.tpl');
                }
                if ($payments['YA_ORG_PAYMENT_MA']) {
                    $this->smarty->assign(array(
                        'pt' => 'MA',
                        'buttontext' => $this->l('Payment via MasterPass.')
                    ));
                    $display .= $this->display(__FILE__, 'kassa.tpl');
                }

                if ($payments['YA_ORG_PAYMENT_QW']) {
                    $this->smarty->assign(array(
                        'pt' => 'QW',
                        'buttontext' => $this->l('Payment via QIWI Wallet.')
                    ));
                    $display .= $this->display(__FILE__, 'kassa.tpl');
                }
                if ($payments['YA_ORG_PAYMENT_QP']) {
                    $this->smarty->assign(array(
                        'pt' => 'QP',
                        'buttontext' => $this->l('Payment through a trusted payment (Kuppi.ru).')
                    ));
                    $display .= $this->display(__FILE__, 'kassa.tpl');
                }
            } else {
                $display .= $this->display(__FILE__, 'kassa_outside.tpl');
            }
        }

        return $display;
    }

    public function hookdisplayPaymentReturn($params)
    {
        if (!$this->active) {
            return ;
        }

        if (!$order=$params['objOrder']) {
            return;
        }

        if ($this->context->cookie->id_customer!=$order->id_customer) {
            return;
        }
        if (!$order->hasBeenPaid()) {
            return;
        }
        $this->smarty->assign(array(
            'products' => $order->getProducts()
        ));
        return $this->display(__FILE__, 'paymentReturn.tpl');
    }

    public function hookdisplayOrderConfirmation($params)
    {
        if (!Configuration::get('YA_METRIKA_ACTIVE')) {
            return false;
        }

        $ret = array();
        $ret['order_price'] = $params['total_to_pay'].' '.$params['currency'];
        $ret['order_id'] = $params['objOrder']->id;
        $ret['currency'] = $params['currencyObj']->iso_code;
        $ret['payment'] = $params['objOrder']->payment;
        $products = array();
        foreach ($params['objOrder']->getCartProducts() as $k => $product) {
            $products[$k]['id'] = $product['product_id'];
            $products[$k]['name'] = $product['product_name'];
            $products[$k]['quantity'] = $product['product_quantity'];
            $products[$k]['price'] = $product['product_price'];
        }

        $ret['goods'] = $products;
        $data = '<script>
                $(window).load(function() {
                    if(celi_order)
                        metrikaReach(\'metrikaOrder\', '.Tools::jsonEncode($ret).');
                });
                </script>
        ';

        return $data;
    }

    public function hookdisplayHeader()
    {
        $this->context->controller->addCSS($this->_path.'/views/css/main.css');
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
    }

    protected function renderForm($mod, $vars, $form)
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit'.$mod.'Module';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->fields_value = $vars;
        $p2p_redirect = $this->context->link->getModuleLink($this->name, 'redirect');
        $kassa_check = $this->context->link->getModuleLink($this->name, 'paymentkassa');
        //$kassa_aviso = $this->context->link->getModuleLink($this->name, 'paymentkassa');
        //$kassa_success = '';//$this->context->link->getModuleLink($this->name, 'success');
        //$kassa_fail = $this->context->link->getModuleLink($this->name, 'fail');
        $api_pokupki = _PS_BASE_URL_.__PS_BASE_URI__.'yamodule/pokupki';
        $redir = _PS_BASE_URL_.__PS_BASE_URI__.'modules/yamodule/callback.php';
        $market_list = $this->context->link->getModuleLink($this->name, 'generate');
        $helper->fields_value['YA_MARKET_YML'] = $market_list;
        $helper->fields_value['YA_ORG_CHECKORDER'] = $kassa_check;
        //$helper->fields_value['YA_ORG_AVISO'] = $kassa_aviso;
        //$helper->fields_value['YA_ORG_FAIL'] = $kassa_fail;
        //TODO
        $helper->fields_value['YA_ORG_SUCCESS'] = "Страницы с динамическими адресами";
        $helper->fields_value['YA_P2P_REDIRECT'] = $p2p_redirect;
        $helper->fields_value['YA_POKUPKI_APISHOP'] = $api_pokupki;
        $helper->fields_value['YA_MARKET_REDIRECT'] = $helper->fields_value['YA_METRIKA_REDIRECT'] = $redir;
        if ($mod == 'Pokupki') {
            $carriers = Carrier::getCarriers(Context::getContext()->language->id, true, false, false, null, 5);
            foreach ($carriers as $a) {
                $array = unserialize(Configuration::get('YA_POKUPKI_CARRIER_SERIALIZE'));
                $helper->fields_value['YA_POKUPKI_DELIVERY_'.$a['id_carrier']]
                    = isset($array[$a['id_carrier']]) ? $array[$a['id_carrier']] : 'POST';
            }
        }
        return $helper->generateForm(array($form));
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency((int)$cart->id_currency);
        $currencies_module = $this->getCurrency();

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
    }

    public function descriptionError($error)
    {
        $error_array = array(
            'invalid_request' => $this->l(
                'Your request is missing required parameters or settings are incorrect or invalid values'
            ),
            'invalid_scope' => $this->l(
                'The scope parameter is missing or has an invalid value or a logical contradiction'
            ),
            'unauthorized_client' => $this->l(
                'Invalid parameter client_id, or the application does not have the'.
                ' right to request authorization (such as its client_id blocked Yandex.Money)'
            ),
            'access_denied' => $this->l('Has declined a request authorization application'),
            'invalid_grant' => $this->l(
                'The issue access_token denied. Issued a temporary token is not '.
                'Google search or expired, or on the temporary token is issued access_token (second '.
                'request authorization token with the same time token)'
            ),
            'illegal_params' => $this->l('Required payment options are not available or have invalid values.'),
            'illegal_param_label' => $this->l('Invalid parameter value label'),
            'phone_unknown' => $this->l('A phone number is not associated with a user account or payee'),
            'payment_refused' => $this->l(
                'The store refused to accept payment (for example, a user tried '.
                'to pay for a product that isn\'t in the store)'
            ),
            'limit_exceeded' => $this->l(
                'Exceeded one of the limits on operations: on the amount of the '.
                'transaction for authorization token issued; transaction amount for the period of time'.
                ' for the token issued by the authorization; Yandeks.Deneg restrictions '.
                'for different types of operations.'
            ),
            'authorization_reject' => $this->l(
                'In payment authorization is denied. Possible reasons are:'.
                ' transaction with the current parameters is not available to the user; person does not'.
                ' accept the Agreement on the use of the service "shops".'
            ),
            'contract_not_found' => $this->l('None exhibited a contract with a given request_id'),
            'not_enough_funds' => $this->l(
                'Insufficient funds in the account of the payer. '.
                'Need to recharge and carry out a new delivery'
            ),
            'not-enough-funds' => $this->l(
                'Insufficient funds in the account of the payer.'.
                ' Need to recharge and carry out a new delivery'
            ),
            'money_source_not_available' => $this->l(
                'The requested method of payment (money_source) '.
                'is not available for this payment'
            ),
            'illegal_param_csc' => $this->l('Tsutstvuet or an invalid parameter value cs'),
            'payment_refused' => $this->l('Shop for whatever reason, refused to accept payment.')
        );
        if (array_key_exists($error, $error_array)) {
            $return = $error_array[$error];
        } else {
            $return = $error;
        }
        return $return;
    }

    public function getErr($id)
    {
        $error = array(
            '0' => $this->l('Technical error or refund denied for this method of payment'),
            '10' => $this->l('Error parsing XML document. '),
            '50' => $this->l(
                'It is impossible to open the digital signature PKCS#7 '.
                'data integrity error digital signature'
            ),
            '51' => $this->l(
                'The TSA is not confirmed (the data of the digital signature'.
                ' do not match with the transferred document)'
            ),
            '53' => $this->l('The request signed by the certificate that is unknown to Yandex.Money'),
            '55' => $this->l('Have expired certificate store'),
            '110' => $this->l('The store does not have rights to perform the operation requested.'),
            '111' => $this->l('Invalid value for the requestDT'),
            '112' => $this->l('Incorrect value of the invoiceId parameter'),
            '113' => $this->l('Invalid value for parameter shopId'),
            '114' => $this->l('Invalid value for the orderNumber'),
            '115' => $this->l('Invalid value for the clientorderid parameter'),
            '117' => $this->l('Invalid value for status parameter'),
            '118' => $this->l('Invalid parameter value from'),
            '119' => $this->l('Invalid parameter value till'),
            '120' => $this->l('Invalid value for the orderId'),
            '200' => $this->l('Invalid value for the outputFormat'),
            '201' => $this->l('Invalid parameter value csvdelimiter parameter'),
            '202' => $this->l('Invalid parameter value orderCreatedDatetimeGreaterOrEqual'),
            '203' => $this->l('Invalid parameter value orderCreatedDatetimeLessOrEqual'),
            '204' => $this->l('Invalid parameter value paid'),
            '205' => $this->l('Incorrect value параметраpaymentDatetimeGreaterOrequal'),
            '206' => $this->l('Incorrect value параметраpaymentDatetimeLessOrEqual'),
            '207' => $this->l('Incorrect value параметраoutputFields'),
            '208' => $this->l('In a query specified an empty range creation time order'),
            '209' => $this->l('Is specified in the request is too large a range of the order creation time'),
            '210' => $this->l('In a query specified an empty range time of order payment'),
            '211' => $this->l('Is specified in the request is too large a time range of order payment'),
            '212' => $this->l('The logical contradiction between the range of dates of payment and the "paid"'),
            '213' => $this->l('There are no condition sample'),
            '214' => $this->l('In the query by order number (orderNumber) do not specify the ID of the shop (shopId)'),
            '215' => $this->l(
                'In the request for transaction number'.
                ' (invoiceId) is not specified, the ID of the shop (shopId)'
            ),
            '216' => $this->l('The result contains too many items'),
            '217' => $this->l('Invalid value for the partial'),
            '402' => $this->l('Incorrect value amount'),
            '403' => $this->l('Invalid currency value'),
            '404' => $this->l('Invalid value the reason for the return'),
            '405' => $this->l('Non-unique operation number'),
            '410' => $this->l('The order is not paid. A refund is impossible'),
            '411' => $this->l('Unsuccessful delivery status notification translation'),
            '412' => $this->l('The transfer currency differs from specified in the request'),
            '413' => $this->l('The refund amount specified in the request, exceeds the amount of the transfer'),
            '414' => $this->l('The translation was returned earlier'),
            '415' => $this->l('The order with the specified transaction number (invoiceId) missing'),
            '416' => $this->l('Insufficient funds for the operation'),
            '417' => $this->l('The payer\'s account is closed. A refund isn\'t possible.'),
            '418' => $this->l('Payer\'s account blocked. A refund isn\'t possible.'),
            '419' => $this->l('The remaining amount after the refund of part of the translation is less then 1 ruble'),
            '424' => $this->l('Forbidden refund part of the amount for this payment method'),
            '601' => $this->l('Not allowed to make payments with Bank cards in favour of the store'),
            '602' => $this->l('Repeat this payment is prohibited'),
            '603' => $this->l('For this operation mandatory orderNumber'),
            '604' => $this->l('Invalid parameter value cvv'),
            '606' => $this->l('The operation of this map is prohibited'),
            '607' => $this->l('Limit exceeded. The operation cannot be performed on the map'),
            '608' => $this->l('Insufficient funds for the transaction on the card'),
            '609' => $this->l('Technical error. The operation cannot be performed on the map'),
            '611' => $this->l('Has expired the Bank card'),
            '612' => $this->l('The operation of this map is prohibited'),
            '1000' => $this->l('Technical error')
        );

        if (!isset($error[$id])) {
            return $id;
        }

        return $error[$id];
    }
}
