<?php

class SecurityController extends FrontController
{
    public $auth = true;
    public $php_self = 'security';
    public $authRedirection = 'security';
    public $ssl = true;

    public function init()
    {
        parent::init();
        $this->customer = $this->context->customer;
    }

    /**
     * Start forms process
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        // $origin_newsletter = (bool)$this->customer->newsletter;

        if (Tools::isSubmit('submitSecurity'))
        {

            if (Tools::getIsset('old_passwd'))
                $old_passwd = trim(Tools::getValue('old_passwd'));

            if (!Tools::getIsset('old_passwd') || (Tools::encrypt($old_passwd) != $this->context->cookie->passwd))
                $this->errors[] = Tools::displayError('The password you entered is incorrect.');
            elseif (Tools::getValue('passwd') != Tools::getValue('confirmation'))
                $this->errors[] = Tools::displayError('The password and confirmation do not match.');
            else
                $this->errors = array_merge($this->errors, $this->customer->validateController());

            if (Tools::getValue('passwd'))
                $this->context->cookie->passwd = trim($this->customer->passwd);
            else
                $this->errors[] = Tools::displayError('Please type new password.');

            if (!count($this->errors)){
                if ($this->customer->update())
                    $this->context->smarty->assign('confirmation', 1);
                else
                    $this->errors[] = Tools::displayError('The information cannot be updated.');
            }
        }
        else
            $_POST = array_map('stripslashes', $this->customer->getFields());

        return $this->customer;
    }
    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign('field_required', $this->context->customer->validateFieldsRequiredDatabase());

        $this->setTemplate(_PS_THEME_DIR_.'security.tpl');
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS(_THEME_CSS_DIR_.'security.css');
        // $this->addJS(_PS_JS_DIR_.'validate.js');
    }
}