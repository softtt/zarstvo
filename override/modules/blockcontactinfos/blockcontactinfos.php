<?php

if (!defined('_CAN_LOAD_FILES_'))
    exit;

class BlockcontactinfosOverride extends Blockcontactinfos
{
    protected static $contact_fields = array(
        'BLOCKCONTACTINFOS_COMPANY',
        'BLOCKCONTACTINFOS_ADDRESS',
        'BLOCKCONTACTINFOS_PHONE',
        'BLOCKCONTACTINFOS_EMAIL',
        'BLOCKCONTACTINFOS_WHOLESALE_PHONE',
        'BLOCKCONTACTINFOS_WHOLESALE_EMAIL',
    );

    public function install()
    {
        Configuration::updateValue('BLOCKCONTACTINFOS_COMPANY', Configuration::get('PS_SHOP_NAME'));
        Configuration::updateValue('BLOCKCONTACTINFOS_ADDRESS', trim(preg_replace('/ +/', ' ', Configuration::get('PS_SHOP_ADDR1').' '.Configuration::get('PS_SHOP_ADDR2')."\n".Configuration::get('PS_SHOP_CODE').' '.Configuration::get('PS_SHOP_CITY')."\n".Country::getNameById(Configuration::get('PS_LANG_DEFAULT'), Configuration::get('PS_SHOP_COUNTRY_ID')))));
        Configuration::updateValue('BLOCKCONTACTINFOS_PHONE', Configuration::get('PS_SHOP_PHONE'));
        Configuration::updateValue('BLOCKCONTACTINFOS_EMAIL', Configuration::get('PS_SHOP_EMAIL'));
        Configuration::updateValue('BLOCKCONTACTINFOS_WHOLESALE_PHONE', Configuration::get('PS_SHOP_PHONE'));
        Configuration::updateValue('BLOCKCONTACTINFOS_WHOLESALE_EMAIL', Configuration::get('PS_SHOP_EMAIL'));
        $this->_clearCache('blockcontactinfos.tpl');
        return (Module::install() && $this->registerHook('header') && $this->registerHook('footer'));
    }

    public function getContent()
    {
        $html = '';
        if (Tools::isSubmit('submitModule'))
        {
            foreach (BlockcontactinfosOverride::$contact_fields as $field)
                Configuration::updateValue($field, Tools::getValue($field));
            $this->_clearCache('blockcontactinfos.tpl');
            $html = $this->displayConfirmation($this->l('Configuration updated'));
        }

        return $html.$this->renderForm();
    }


    public function hookFooter($params)
    {
        if (!$this->isCached('blockcontactinfos.tpl', $this->getCacheId()))
            foreach (BlockcontactinfosOverride::$contact_fields as $field)
                $this->smarty->assign(strtolower($field), Configuration::get($field));
        return $this->display(__FILE__, 'blockcontactinfos.tpl', $this->getCacheId());
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Company name'),
                        'name' => 'BLOCKCONTACTINFOS_COMPANY',
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Address'),
                        'name' => 'BLOCKCONTACTINFOS_ADDRESS',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Phone number'),
                        'name' => 'BLOCKCONTACTINFOS_PHONE',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Email'),
                        'name' => 'BLOCKCONTACTINFOS_EMAIL',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Wholesale phone number'),
                        'name' => 'BLOCKCONTACTINFOS_WHOLESALE_PHONE',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Wholesale email'),
                        'name' => 'BLOCKCONTACTINFOS_WHOLESALE_EMAIL',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table =  $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => array(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        foreach (BlockcontactinfosOverride::$contact_fields as $field)
            $helper->tpl_vars['fields_value'][$field] = Tools::getValue($field, Configuration::get($field));
        return $helper->generateForm(array($fields_form));
    }

}
