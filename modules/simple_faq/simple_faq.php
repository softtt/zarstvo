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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2015 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Simple_faq extends Module
{
    protected $config_form = false;
    private $_html = '';
    private $_postErrors = array();


    public function __construct()
    {
        $this->name = 'simple_faq';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'Smart Raccoon';
        $this->need_instance = 0;
        $this->is_configurable = true;
        $this->controllers = array('list');

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Simple FAQ');
        $this->description = $this->l('Frequently asked questions');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->context = Context::getContext();
        include_once $this->getLocalPath() . 'models/Question.php';
        $config = Configuration::getMultiple(array('FAQ_EMAIL'));
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (file_exists(_PS_MODULE_DIR_ . 'simple_faq/sql/install.php'))
            include_once(_PS_MODULE_DIR_ . 'simple_faq/sql/install.php');

        // Tab
        $parent_tab = new Tab();

        $parent_tab->name = array();
        foreach (Language::getLanguages(true) as $lang)
            $parent_tab->name[$lang['id_lang']] = 'Вопросы и ответы';

        $parent_tab->class_name = 'AdminSimpleFaq';
        $parent_tab->id_parent = 0;
        $parent_tab->module = $this->name;
        $parent_tab->add();

        return parent::install() &&
        $this->registerHook('header') &&
        $this->registerHook('backOfficeHeader') &&
        $this->registerHook('productTabContent') &&
        $this->registerHook('productTab');

    }

    public function uninstall()
    {
        $tab = Tab::getInstanceFromClassName('AdminSimpleFaq');
        $tab->delete();
        Configuration::deleteByName('FAQ_EMAIL');


        return parent::uninstall();
    }


    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitFaqModule')) == true) {
            $this->_postValidation();
            if (!count($this->_postErrors))
                $this->postProcess();
            else
                foreach ($this->_postErrors as $err)
                    $this->_html .= $this->displayError($err);
        } else
            $this->_html .= '<br />';

        $this->_html .= $this->renderForm();

        return $this->_html;

    }

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
                        'type' => 'text',
                        'label' => $this->l('FAQ_EMAIL'),
                        'name' => 'FAQ_EMAIL',
                        'desc' => $this->l('FAQ_EMAIL'),
                        'required' => true
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
        $helper->submit_action = 'submitFaqModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));
    }

    private function _postValidation()
    {
        if (Tools::isSubmit('submitFaqModule')) {
            if (!Tools::getValue('FAQ_EMAIL'))
                $this->_postErrors[] = $this->l('FAQ_EMAIL are required.');
        }
    }

    protected function getConfigFormValues()
    {
        return array(
            'FAQ_EMAIL' => Tools::getValue('FAQ_EMAIL', Configuration::get('FAQ_EMAIL')),
            'FAQ_MAIL_TEMPLATE' => Tools::getValue('FAQ_MAIL_TEMPLATE', Configuration::get('FAQ_MAIL_TEMPLATE')),
        );
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/simple_faq-admin.css');
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    public function hookProductTabContent($params)
    {

        $questions = Question::getQuestions();
        $image = Product::getCover((int)Tools::getValue('id_product'));
        $this->context->smarty->assign(array(
            'id_image_form' => $image['id_image'],
            'id_product_form' => (int)Tools::getValue('id_product'),
            'questions' => $questions,
            'email' => $this->context->cookie->email ? $this->context->cookie->email : '',
            'errors' => $this->errors,
            'questions_controller_url' => $this->context->link->getModuleLink('simple_faq'),
        ));

        if ($this->errors) {
            $this->context->smarty->assign(array(

                'post_name' => Tools::getValue('customer_name', null),
                'post_email' => Tools::getValue('email', null),
                'post_question' => Tools::getValue('question', null),
            ));
        }

        if (Tools::getIsset('success'))
            $this->context->smarty->assign('confirmation', true);


        return $this->display(__FILE__, 'list.tpl');
    }

    public function hookProductTab($params)
    {
        return;
    }
}
