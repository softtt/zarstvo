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

class PH_RecentPosts extends Module
{
    public function __construct()
    {
        $this->name = 'ph_recentposts';
        $this->tab = 'front_office_features';
        $this->version = '1.0.10';
        $this->author = 'www.PrestaHome.com';
        $this->need_instance = 0;
        $this->is_configurable = 1;
        $this->ps_versions_compliancy['min'] = '1.5.6.1';
        $this->ps_versions_compliancy['max'] = '1.6.1.0';
        $this->secure_key = Tools::encrypt($this->name);

        if(!Module::isInstalled('ph_simpleblog') || !Module::isEnabled('ph_simpleblog'))
            $this->warning = $this->l('You have to install and activate ph_simpleblog before use ph_recentposts');

        parent::__construct();

        $this->displayName = $this->l('Blog for PrestaShop - Recent posts');
        $this->description = $this->l('Widget to display recently added posts from PrestaHome SimpleBlog module');

        $this->confirmUninstall = $this->l('Are you sure you want to delete this module ?');
    }

    public function install()
    {
        // Hooks & Install
        return (parent::install() 
                && $this->prepareModuleSettings() 
                && $this->registerHook('displaySimpleBlogRecentPosts') 
                && $this->registerHook('displayHome') 
                && $this->registerHook('displayHeader') 
                && $this->registerHook('displayLeftColumn'));
    }

    public function prepareModuleSettings()
    {
        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        return true;
    }

    public function preparePosts($nb = 4, $cat = null)
    {
        $featured = false;

        if($cat == 9999)
        {
            $cat = 0;
            $featured = true;
        }

        if(!Module::isInstalled('ph_simpleblog') || !Module::isEnabled('ph_simpleblog'))
            return false;

        if(!isset($nb) || !isset($cat))
            return false;

        require_once _PS_MODULE_DIR_ . 'ph_simpleblog/models/SimpleBlogPost.php';

        $id_lang = $this->context->language->id;

        $posts = SimpleBlogPost::getPosts($id_lang, $nb, $cat, null, true, 'sbp.date_add', 'DESC', null, $featured);

        return $posts;
    }

    public function prepareSimpleBlogRecentPosts()
    {
        if(!$posts = $this->preparePosts(Configuration::get('PH_RECENTPOSTS_NB'), Configuration::get('PH_RECENTPOSTS_CAT')))
            return;
        
        $id_lang = $this->context->language->id;

        $gridColumns = Configuration::get('PH_RECENTPOSTS_GRID_COLUMNS');
        $blogLayout = Configuration::get('PH_RECENTPOSTS_LAYOUT');

        $this->context->smarty->assign(array(
            'blogLayout' => $blogLayout,
            'columns' => $gridColumns,
            'gallery_dir' => _MODULE_DIR_.'ph_simpleblog/galleries/',
            'recent_posts' => $posts,
            'tpl_path' => dirname(__FILE__).'/views/templates/hook/',
        ));
    }

    public function hookDisplaySimpleBlogRecentPosts($params)
    {
        $this->prepareSimpleBlogRecentPosts();

        if(isset($params['template']))
             return $this->display(__FILE__, $params['template'].'.tpl');
        else
            return $this->hookDisplayHome($params);
    }

    public function hookDisplayHome($params)
    {
        $this->prepareSimpleBlogRecentPosts();

        return $this->display(__FILE__, 'recent.tpl');
    }

    public function hookDisplayLeftColumn($params)
    {
        return;
    }
}
