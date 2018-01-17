<?php
/**
 * Add-on for yamodule payment.
 * Save cart for customer if payment on separate page was not finished.
 * So customer could repeat last order.
 *
 * @author Victor Scherba <dev@smart-raccoon.com>
 * @link   http://smart-raccoon.com
 */

if (!defined('_PS_VERSION_'))
    exit;

class SaveLastCart extends Module
{
    public function __construct()
    {
        $this->name = 'savelastcart';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'Smart Raccoon';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Save last cart');
        $this->description = $this->l('Save last cart after order checkout.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('SAVELASTCART_NAME'))
            $this->warning = $this->l('No name provided');
    }


    public function install()
    {
        if (!Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'cart` ADD `copy_of_cart_id` INT UNSIGNED NOT NULL DEFAULT 0;'))
            return false;

        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);

        if (!parent::install() ||
            !$this->registerHook('actionValidateOrder') ||
            !$this->registerHook('actionOrderStatusPostUpdate') ||
            !$this->registerHook('actionOrderStatusUpdate') ||
            !$this->registerHook('actionPaymentConfirmation') ||
            !Configuration::updateValue('SAVELASTCART_NAME', 'save last cart')
        )
            return false;

        return true;
    }

    public function uninstall()
    {
        if (!Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'cart` DROP `copy_of_cart_id`;'))
            return false;

        if (!parent::uninstall() ||
            !Configuration::deleteByName('SAVELASTCART_NAME')
        )
            return false;

        return true;
    }

    public function hookActionValidateOrder($params)
    {
        if ($params['order']->module == 'yamodule')
        {
            // Duplicate cart to make possible process checkout again on fails.
            $oldCart = $params['cart'];

            $duplication = $oldCart->duplicate();
            $cart = $duplication['cart'];
            $cart->copy_of_cart_id = $oldCart->id;
            $cart->save();

            $this->context->cookie->id_cart = $cart->id;
            $context = $this->context;
            $context->cart = $cart;

            CartRule::autoAddToCart($context);
            $this->context->cookie->write();
        }

        return;
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        if (in_array($params['newOrderStatus']->id, array(Configuration::get('PS_OS_PAYMENT'), Configuration::get('PS_OS_WS_PAYMENT'))))
            $this->hookActionPaymentConfirmation($params);
        return;
    }

    public function hookActionOrderStatusUpdate($params)
    {
        if (in_array($params['newOrderStatus']->id, array(Configuration::get('PS_OS_PAYMENT'), Configuration::get('PS_OS_WS_PAYMENT'))))
            $this->hookActionPaymentConfirmation($params);
        return;
    }

    public function hookActionPaymentConfirmation($params)
    {
        $order = new Order($params['id_order']);
        $order_cart = new Cart($order->id_cart);

        $result = Db::getInstance()->getRow('SELECT `id_cart` FROM '._DB_PREFIX_.'cart WHERE `copy_of_cart_id` = '.(int)$order_cart->id);
        if (!$result || empty($result) || !array_key_exists('id_cart', $result))
            return false;

        $duplicated_cart = new Cart($result['id_cart']);
        $duplicated_cart->delete();

        return;
    }
}
