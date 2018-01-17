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

class Ph_simpleblog_search extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'ph_simpleblog_search';
        $this->tab = 'search_filter';
        $this->version = '1.0.0';
        $this->author = 'Vlad Chachiev';
        $this->need_instance = 0;
        $this->controllers = array('search');

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Blog Search');
        $this->description = $this->l('Blog posts search module for ph_simpleblog');

        $this->confirmUninstall = $this->l('Удалить модуль поиска по блогу?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {

        return parent::install() &&
            $this->registerHook('displayRightColumn');
    }

    public function uninstall()
    {

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output;
    }


    public function hookDisplayLeftColumn()
    {
        return;
    }

    public function hookDisplayRightColumn() // form for search
    {
        $this->context->controller->addJS($this->_path.'views/js/front.js');
        $this->context->controller->addCSS($this->_path.'views/css/front.css');

        if (!$this->isCached('blog_post_search.tpl', $this->getCacheId()) ) {
            $this->context->smarty->assign(
                array(
                    'worker_link'    => Context::getContext()->link->getModuleLink('ph_simpleblog_search', 'search'),
                    'blog_search_query' => Tools::getValue('search_blog_query'),
                )
            );
        }

        return $this->display(__FILE__, 'blog_post_search.tpl', $this->getCacheId());
    }
}