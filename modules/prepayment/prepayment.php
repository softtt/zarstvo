<?php
/*
* 2007-2014 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2014 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_'))
	exit;

class Prepayment extends PaymentModule
{
	private $_html = '';
	private $_postErrors = array();

	public $details;
	public $extra_mail_vars;
	public function __construct()
	{
		$this->name = 'prepayment';
		$this->tab = 'payments_gateways';
		$this->version = '1.0.0';
		$this->author = 'Smart Raccoon';
		$this->controllers = array('payment', 'validation');
		$this->is_eu_compatible = 1;

		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		$config = Configuration::getMultiple(array('PREPAYMENT_DETAILS', 'PREPAYMENT_PENDING_ORDER_STATUS_ID', 'PREPAYMENT_AVAILABLE_DELIVERY_METHODS'));
		if (!empty($config['PREPAYMENT_DETAILS']))
			$this->details = $config['PREPAYMENT_DETAILS'];
		if (!empty($config['PREPAYMENT_PENDING_ORDER_STATUS_ID']))
			$this->order_status_id = $config['PREPAYMENT_PENDING_ORDER_STATUS_ID'];
		if (!empty($config['PREPAYMENT_AVAILABLE_DELIVERY_METHODS']))
			$this->deliveries = $config['PREPAYMENT_AVAILABLE_DELIVERY_METHODS'];

		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Prepayment');
		$this->description = $this->l('Accept payments on prepayment terms.');
		$this->confirmUninstall = $this->l('Are you sure about removing these details?');
		if (!isset($this->details))
			$this->warning = $this->l('Account details must be configured before using this module.');
		if (!count(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this module.');

		$this->extra_mail_vars = array(
										'{prepayment_details}' => nl2br(Configuration::get('PREPAYMENT_DETAILS'))
										);
	}

	public function install()
	{
		if (!parent::install() || !$this->registerHook('payment') || ! $this->registerHook('displayPaymentEU') || !$this->registerHook('paymentReturn'))
			return false;
		return true;
	}

	public function uninstall()
	{
		if (!Configuration::deleteByName('PREPAYMENT_DETAILS')
				|| !Configuration::deleteByName('PREPAYMENT_PENDING_ORDER_STATUS_ID')
				|| !Configuration::deleteByName('PREPAYMENT_AVAILABLE_DELIVERY_METHODS')
				|| !parent::uninstall())
			return false;
		return true;
	}

	private function _postValidation()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			if (!Tools::getValue('PREPAYMENT_DETAILS'))
				$this->_postErrors[] = $this->l('Account details are required.');
			elseif (!Tools::getValue('PREPAYMENT_PENDING_ORDER_STATUS_ID'))
				$this->_postErrors[] = $this->l('Mailing template id is required.');
		}
	}

	private function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			Configuration::updateValue('PREPAYMENT_DETAILS', Tools::getValue('PREPAYMENT_DETAILS'));
			Configuration::updateValue('PREPAYMENT_PENDING_ORDER_STATUS_ID', Tools::getValue('PREPAYMENT_PENDING_ORDER_STATUS_ID'));
			Configuration::updateValue('PREPAYMENT_AVAILABLE_DELIVERY_METHODS', Tools::getValue('PREPAYMENT_AVAILABLE_DELIVERY_METHODS'));
		}
		$this->_html .= $this->displayConfirmation($this->l('Settings updated'));
	}

	private function _displayPrepayment()
	{
		return $this->display(__FILE__, 'infos.tpl');
	}

	public function getContent()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			$this->_postValidation();
			if (!count($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors as $err)
					$this->_html .= $this->displayError($err);
		}
		else
			$this->_html .= '<br />';

		$this->_html .= $this->_displayPrepayment();
		$this->_html .= $this->renderForm();

		return $this->_html;
	}

	public function hookPayment($params)
	{
		if (!$this->active)
			return;
		if (!$this->checkCurrency($params['cart']))
			return;
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
			'this_path' => $this->_path,
			'this_path_bw' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
			'disable' => $disable
		));
		return $this->display(__FILE__, 'payment.tpl');
	}

	public function hookDisplayPaymentEU($params)
	{
		if (!$this->active)
			return;

		if (!$this->checkCurrency($params['cart']))
			return;


		return array(
			'cta_text' => $this->l('Pay by Prepayment'),
			'logo' => Media::getMediaPath(dirname(__FILE__).'/prepayment.png'),
			'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true)
		);
	}

	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return;

		$state = $params['objOrder']->getCurrentState();
		if (in_array($state, array(Configuration::get('PREPAYMENT_PENDING_ORDER_STATUS_ID'), Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'))))
		{
			$this->smarty->assign(array(
				'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
				'prepaymentDetails' => Tools::nl2br($this->details),
				'status' => 'ok',
				'id_order' => $params['objOrder']->id
			));
			if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference))
				$this->smarty->assign('reference', $params['objOrder']->reference);
		}
		else
			$this->smarty->assign('status', 'failed');
		return $this->display(__FILE__, 'payment_return.tpl');
	}

	public function checkCurrency($cart)
	{
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);

		if (is_array($currencies_module))
			foreach ($currencies_module as $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}

	public function renderForm()
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
						'name' => 'PREPAYMENT_DETAILS',
						'desc' => $this->l('Details'),
						'required' => true
					),
					array(
						'type' => 'text',
						'label' => $this->l('Pending state ID'),
						'name' => 'PREPAYMENT_PENDING_ORDER_STATUS_ID',
						'desc' => $this->l('Pending state ID'),
						'required' => true
					),
					array(
						'type' => 'text',
						'label' => $this->l('Available delivery methods IDs, separated by commas'),
						'name' => 'PREPAYMENT_AVAILABLE_DELIVERY_METHODS',
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
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'btnSubmit';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues()
	{
		return array(
			'PREPAYMENT_DETAILS' => Tools::getValue('PREPAYMENT_DETAILS', Configuration::get('PREPAYMENT_DETAILS')),
			'PREPAYMENT_PENDING_ORDER_STATUS_ID' => Tools::getValue('PREPAYMENT_PENDING_ORDER_STATUS_ID', Configuration::get('PREPAYMENT_PENDING_ORDER_STATUS_ID')),
			'PREPAYMENT_AVAILABLE_DELIVERY_METHODS' => Tools::getValue('PREPAYMENT_AVAILABLE_DELIVERY_METHODS', Configuration::get('PREPAYMENT_AVAILABLE_DELIVERY_METHODS')),

		);
	}
}
