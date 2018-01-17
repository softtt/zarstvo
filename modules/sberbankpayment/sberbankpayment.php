<?php
/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Sberbankpayment extends PaymentModule
{
    protected $config_form = false;
    public $details;
    public $extra_mail_vars;
    private $_html = '';
    private $_postErrors = array();


    public function __construct()
    {
        $this->name = 'sberbankpayment';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Vitaliy Sheynin';
        $this->need_instance = 0;
        $this->controllers = array('payment', 'validation');
        $this->is_eu_compatible = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Перевод на карту сбербанка');
        $this->description = $this->l('Перевод на карту сбербанка');

        $config = Configuration::getMultiple(array('SBER_BANK_DETAILS','SBER_BANK_PAYMENT_AVAILABLE_DELIVERY_METHODS','PS_OS_SBERBANK'));

        if (!empty($config['SBER_BANK_DETAILS'])) {
            $this->details = $config['SBER_BANK_DETAILS'];
        }

        if (!empty($config['SBER_BANK_PAYMENT_AVAILABLE_DELIVERY_METHODS'])) {
            $this->deliveries = $config['SBER_BANK_PAYMENT_AVAILABLE_DELIVERY_METHODS'];
        }

        $this->extra_mail_vars = [
            '{sberbank_details}' => Configuration::get('SBER_BANK_DETAILS')
        ];

    }


    public function install()
    {
        $order_state = new OrderState(null,$this->context->language->id);
        $order_state->color = '#4169E1';
        $order_state->name = 'Ожидание оплаты на карту Сбербанка';
        $order_state->template = 'sberbank_details';
        $order_state->send_email = 1;
        $order_state->save();
        $order_state_id = $order_state->id;
        Configuration::updateValue('PS_OS_SBERBANK',$order_state_id);

        if (!parent::install()
            || !$this->registerHook('payment')
            || ! $this->registerHook('displayPaymentEU')
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('displayOrderConfirmation')
        ) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {

        Configuration::deleteByName('SBER_BANK_DETAILS');
        Configuration::deleteByName('SBER_BANK_PAYMENT_AVAILABLE_DELIVERY_METHODS');
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitSberbankpaymentModule')) == true) {
            $this->_postValidation();
            if (!count($this->_postErrors))
                $this->postProcess();
            else
                foreach ($this->_postErrors as $err)
                    $this->_html .= $this->displayError($err);
        }
        else
            $this->_html .= '<br />';

        $this->_html .= $this->renderForm();

        return $this->_html;

    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Contact details'),
                    'icon' => 'icon-envelope'
                ),
                'input' => array(
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Details'),
                        'name' => 'SBER_BANK_DETAILS',
                        'desc' => $this->l('Details'),
                        'cols' => 40,
                        'rows' => 10,
                        'class' => 'rte',
                        'autoload_rte' => true,
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Available delivery methods IDs, separated by commas'),
                        'name' => 'SBER_BANK_PAYMENT_AVAILABLE_DELIVERY_METHODS',
                        'desc' => $this->l('Available delivery methods IDs, separated by commas'),
                        'required' => false
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );


        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSberbankpaymentModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'SBER_BANK_DETAILS' => Tools::getValue('SBER_BANK_DETAILS', Configuration::get('SBER_BANK_DETAILS')),
            'SBER_BANK_PAYMENT_AVAILABLE_DELIVERY_METHODS' => Tools::getValue('SBER_BANK_PAYMENT_AVAILABLE_DELIVERY_METHODS', Configuration::get('SBER_BANK_PAYMENT_AVAILABLE_DELIVERY_METHODS')),
        );
    }
    private function _postValidation()
    {
        if (Tools::isSubmit('submitSberbankpaymentModule'))
        {
            if (!Tools::getValue('SBER_BANK_DETAILS'))
                $this->_postErrors[] = $this->l('Account details are required.');
        }
    }
    public function hookPayment($params)
    {
        $currency_id = $params['cart']->id_currency;
        $currency = new Currency((int)$currency_id);
        if (!reset($params['cart']->getDeliveryOption()) && !Tools::isSubmit('delivery_option'))
            return;
        $delivery_option_ids = explode(',', $this->deliveries);
        if (Tools::isSubmit('delivery_option'))
            $cart_delivery = rtrim(reset(Tools::getValue('delivery_option')), ',');
        else
            $cart_delivery = rtrim(reset($params['cart']->getDeliveryOption()), ',');

        $disable = false;
        if (!in_array($cart_delivery, $delivery_option_ids))
            $disable = true;

        $this->smarty->assign(array(
            'module_dir'=> $this->_path,
            'this_path_bw' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
			'disable' => $disable));

        return $this->display(__FILE__, 'payment.tpl');
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            $html = false;
            if ($key == 'SBER_BANK_DETAILS') {
                $html = true;
            }

            Configuration::updateValue($key, Tools::getValue($key), $html);
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    /**
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     */


    public function hookDisplayPaymentEU($params)
    {
        if (!$this->active)
            return;

        if (!$this->checkCurrency($params['cart']))
            return;


        return array(
            'cta_text' => $this->l('Заплатить переводом на карту сбербанка'),
            'logo' => Media::getMediaPath(dirname(__FILE__).'/cashpayment.png'),
            'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true)
        );
    }

    /**
     * This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params)
    {
        if ($this->active == false)
            return;

        $order = $params['objOrder'];

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR'))
            $this->smarty->assign('status', 'ok');

        $this->smarty->assign(array(
            'id_order' => $order->id,
            'reference' => $order->reference,
            'params' => $params,
            'total' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
        ));

        return $this->display(__FILE__, 'views/templates/hook/confirmation.tpl');
    }

    public function hookDisplayOrderConfirmation($params)
    {
        return Configuration::get('SBER_BANK_DETAILS');
    }
}
