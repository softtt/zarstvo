<?php

/*
* @author    Krystian Podemski <podemski.krystian@gmail.com>
* @site
* @copyright  Copyright (c) 2014 impSolutions (http://www.impsolutions.pl) && PrestaHome.com
* @license    You only can use module, nothing more!
*
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

if(file_exists(_PS_MODULE_DIR_ . 'ph_simpleblog/models/SimpleBlogPost.php'))
    require_once _PS_MODULE_DIR_ . 'ph_simpleblog/models/SimpleBlogPost.php';

if(file_exists(_PS_MODULE_DIR_ . 'ph_relatedposts/models/SimpleBlogRelatedPost.php'))
    require_once _PS_MODULE_DIR_ . 'ph_relatedposts/models/SimpleBlogRelatedPost.php';

class ph_relatedposts extends Module
{
    public function __construct()
    {
        $this->name = 'ph_relatedposts';
        $this->tab = 'front_office_features';
        $this->version = '1.1.1';
        $this->author = 'www.PrestaHome.com';
        $this->need_instance = 0;
        $this->is_configurable = 1;
        $this->ps_versions_compliancy['min'] = '1.5.3.1';
        $this->ps_versions_compliancy['max'] = '1.6.1.0';
        $this->secure_key = Tools::encrypt($this->name);

        if(!Module::isInstalled('ph_simpleblog') || !Module::isEnabled('ph_simpleblog'))
            $this->warning = $this->l('You have to install and activate ph_simpleblog before use ph_relatedposts');

        parent::__construct();

        $this->displayName = $this->l('Blog for PrestaShop - Related Posts');
        $this->description = $this->l('Widget to display posts related to your products from PrestaHome Blog for PrestaShop module');

        $this->confirmUninstall = $this->l('Are you sure you want to delete this module ?');
    }

    public function install()
    {
        // Hooks & Install
        return (parent::install() 
                && $this->prepareModuleSettings() 
                && $this->registerHook('actionObjectProductDeleteAfter') 
                && $this->registerHook('actionObjectSimpleBlogPostDeleteAfter') 
                && $this->registerHook('displaySimpleBlogRelatedPosts') 
                && $this->registerHook('productTabContent') 
                && $this->registerHook('productTab') 
                && $this->registerHook('displayAdminProductsExtra') 
                && $this->registerHook('displayBackOfficeHeader') 
                && $this->registerHook('actionAdminProductsControllerSaveAfter'));
    }

    public function prepareModuleSettings()
    {
        // Database
        $sql = array();
        include (dirname(__file__) . '/init/install_sql.php');
        foreach ($sql as $s) {
            if (!Db::getInstance()->Execute($s)) {
                return false;
            }
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        // Database
        $sql = array();
        include (dirname(__file__) . '/init/uninstall_sql.php');
        foreach ($sql as $s) {
            if (!Db::getInstance()->Execute($s)) {
                return false;
            }
        }

        // Tabs
        $idTabs = array();
        $idTabs[] = Tab::getIdFromClassName('AdminSimpleBlogRelatedPosts');

        foreach ($idTabs as $idTab) {
            if ($idTab) {
                $tab = new Tab($idTab);
                $tab->delete();
            }
        }

        return true;
    }

    public function hookActionObjectProductDeleteAfter($params)
    {
        return SimpleBlogRelatedPost::cleanRelatedForProduct($params['object']->id);
    }

    public function hookActionObjectSimpleBlogPostDeleteAfter($params)
    {
        return SimpleBlogRelatedPost::cleanRelatedForPost($params['object']->id);
    }

    public function hookActionAdminProductsControllerSaveAfter($params)
    {
        $id_product = Tools::getValue('id_product');
        SimpleBlogRelatedPost::cleanRelatedForProduct($id_product);

        $related_posts = Tools::getValue('related_posts', 0);
        if($related_posts)
        {
            foreach($related_posts as $post)
            {
                $instance = new SimpleBlogRelatedPost();
                $instance->id_simpleblog_post = $post;
                $instance->id_product = $id_product;
                $instance->add();
            }    
        }
    }

    public function hookDisplayAdminProductsExtra()
    {
        
        if(!Module::isInstalled('ph_simpleblog') || !Module::isEnabled('ph_simpleblog'))
            return;

        $product = new Product(Tools::getValue('id_product'), false, $this->context->cookie->id_lang);

        $posts = SimpleBlogPost::getSimplePosts($this->context->language->id);

        $selected_posts = array();
        $related_posts = array();

        foreach(SimpleBlogRelatedPost::getByProductId($product->id) as $key => $post)
        {
            $related_posts[] = $post['id_simpleblog_post'];
        }

        if(sizeof($related_posts) > 0)
        {
            $posts = SimpleBlogPost::getSimplePosts($this->context->language->id, null, null, 'NOT IN', $related_posts);
            $selected_posts = SimpleBlogPost::getSimplePosts($this->context->language->id, null, null, 'IN', $related_posts);
        }

        $this->context->smarty->assign(array(
            'product' => $product,
            'posts' => $posts,
            'selected_posts' => $selected_posts,
            'module_path' => $this->_path,
            'secure_key' => $this->secure_key,
            'is_16' => (bool)(version_compare(_PS_VERSION_, '1.6.0', '>=') === true)
        ));
        
        return $this->display(__FILE__, 'admin-tab.tpl');
    }

    /**

    Front end

    */

    public function _prepareRelatedPosts()
    {
        if (!isset($this->context->controller->php_self) || $this->context->controller->php_self != 'product')
            return false;

        $id_product = Tools::getValue('id_product', 0);

        $related_posts = array();

        $relatedPosts = SimpleBlogRelatedPost::getByProductId((int)$id_product);

        if(sizeof($relatedPosts) < 1)
            return false;

        foreach($relatedPosts as $key => $post)
        {
            $related_posts[] = $post['id_simpleblog_post'];
        }

        return SimpleBlogPost::getPosts($this->context->language->id, 999, null, null, true, false, false, null, false, false, null, 'IN', $related_posts);
    }

    public function hookProductTab($params)
    {
        $id_product = Tools::getValue('id_product');

        $posts = SimpleBlogRelatedPost::getByProductId((int)$id_product);

        if(sizeof($posts) < 1)
            return;

        return $this->display(__FILE__, 'product-tab.tpl');
    }

    public function hookProductTabContent($params)
    {
        if(!$posts = $this->_prepareRelatedPosts())
            return;

        $this->context->smarty->assign(array(
            'related_posts' => $posts,
            'blogLayout' => Configuration::get('PH_BLOG_LAYOUT'),
            'gallery_dir' => _MODULE_DIR_.'ph_simpleblog/galleries/',
            'tpl_path' => dirname(__FILE__).'/views/templates/hook/',
        ));

        return $this->display(__FILE__, 'product-tab-content.tpl');
    }

    public function hookDisplayRightColumn($params)
    {
        if(!$posts = $this->_prepareRelatedPosts())
            return;

        $this->context->smarty->assign(array(
            'related_posts_column' => $posts,
            'blogLayout' => Configuration::get('PH_BLOG_LAYOUT'),
            'gallery_dir' => _MODULE_DIR_.'ph_simpleblog/galleries/',
            'tpl_path' => dirname(__FILE__).'/views/templates/hook/',
        ));

        return $this->display(__FILE__, 'column.tpl');
    }

    public function hookDisplayLeftColumn($params)
    {
        return $this->hookDisplayRightColumn($params);
    }

    public function hookDisplayRightColumnProduct($params)
    {
        if(!$posts = $this->_prepareRelatedPosts())
            return;

        $this->context->smarty->assign(array(
            'related_posts_column_product' => $posts,
            'blogLayout' => Configuration::get('PH_BLOG_LAYOUT'),
            'gallery_dir' => _MODULE_DIR_.'ph_simpleblog/galleries/',
            'tpl_path' => dirname(__FILE__).'/views/templates/hook/',
        ));

        return $this->display(__FILE__, 'column-product.tpl');
    }

    public function hookDisplayLeftColumnProduct($params)
    {
        return $this->hookDisplayRightColumnProduct($params);
    }
}