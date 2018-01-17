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

class CallbackDefaultModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();

        $this->context = Context::getContext();
    }

    public function initContent()
    {
        parent::initContent();

        if (Tools::isSubmit('action'))
        {
            switch(Tools::getValue('action'))
            {
                case 'place_callback_order':
                    if (Tools::isSubmit('name') && Tools::isSubmit('phone'))
                    {
                        $name = Tools::getValue('name');
                        $phone = Tools::getValue('phone');
                        $email = Tools::getValue('email');
                        $message = Tools::getValue('message');
                        if ($name && $phone)
                            $this->sendNotification($name, $phone, $email, $message);
                    }
                    else
                        die(Tools::jsonEncode(array('errors' => true, 'error_type' => 'error_validation')));
                    break;
            }
        }
    }

    protected function sendNotification($name, $phone, $email = '', $message = '')
    {
        if (Mail::Send(
            $this->context->language->id,
            'callback_notification',
            Mail::l('New callback order', $this->context->language->id),
            array(
            '{name}' => $name,
            '{phone}' => $phone,
            '{email}' => $email,
            '{message}' => $message,
            ),
            'aprex.x@gmail.com', // to
            null, //to_name
            strval(Configuration::get('PS_SHOP_EMAIL')),
            strval(Configuration::get('PS_SHOP_NAME')),
            null, // File attachment
            null, //mode smtp
            $this->module->getLocalPath().'mails/')
        )
        {
            die(Tools::jsonEncode(array('errors' => false, 'send' => true)));
        }
        else
            die(Tools::jsonEncode(array('errors' => true, 'error_type' => 'error_callback')));
    }
}
