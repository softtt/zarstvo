<?php
/*
* @author    Krystian Podemski <podemski.krystian@gmail.com>
* @site
* @copyright  Copyright (c) 2014-2015 Krystian Podemski - www.PrestaHome.com
* @license    You only can use module, nothing more!
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class PH_Blog_Custom_Second extends Module
{

    private $_html = '';
    public $fields_form;
    public $fields_value;
    public $validation_errors = array();

    public static $cfg_prefix = 'PH_BLOG_CUSTOM_SECOND_';
    
    public function __construct()
    {
        $this->name = 'ph_blog_custom_second';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'www.PrestaHome.com';
        $this->need_instance = 0;
        $this->is_configurable = 1;
        $this->secure_key = Tools::encrypt($this->name);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Blog for PrestaShop - second custom block on homepage');
    }

    public function getDefaults()
    {
        return array(
            'TITLE' => $this->prepareValueForLangs('Title of the second custom block'),
            'FROM' => 'featured',
            'NB_POSTS' => 2,
        );
    }

    public function prepareValueForLangs($value)
    {
        $languages = Language::getLanguages(false);

        $output = array();
        foreach($languages as $language)
        {
            $output[$language['id_lang']] = $value;
        }

        return $output;
    }

    public function install()
    {
        // Hooks & Install
        return (parent::install() 
                && $this->prepareModuleSettings() 
                && $this->registerHook('displayHome') 
            );
    }

    public function prepareModuleSettings()
    {
        foreach($this->getDefaults() as $key => $value)
        {
            Configuration::updateValue(self::$cfg_prefix.$key, $value, true);
        }
       
        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        foreach($this->getDefaults() as $key => $value)
        {
            Configuration::deleteByName(self::$cfg_prefix.$key);
        }

        return true;
    }

    public function getContent()
    {
        $id_shop = (int)$this->context->shop->id;

        $this->initFieldsForm();
        if (isset($_POST['save'.$this->name]))
        {
            $multiLangFields = array();
            foreach($this->getDefaults() as $field_name => $field_value)
            {
                if(is_array($field_value))
                {
                    $multiLangFields[] = self::$cfg_prefix.$field_name;
                }
            }

            foreach ($_POST as $key => $value)
            {
                $fieldName = substr($key, 0, -2);

                if(in_array($fieldName, $multiLangFields))
                {
                    $thisFieldValue = array();
                    foreach(Language::getLanguages(true) as $language)
                    {
                        if(isset($_POST[$fieldName.'_'.$language['id_lang']]))
                        {
                            $thisFieldValue[$language['id_lang']] = $_POST[$fieldName.'_'.$language['id_lang']];
                        }
                    }
                    $_POST[$fieldName] = $thisFieldValue;
                }
            }

            foreach($this->getDefaults() as $field_name => $field_value)
            {
                if(is_array($field_value))
                {
                    Configuration::updateValue($field_name, ${$field_name}, true);
                }
            }
                

            foreach($this->fields_form as $form)
                foreach($form['form']['input'] as $field)
                    if(isset($field['validation']))
                    {
                        $errors = array();       
                        $value = Tools::getValue($field['name']);
                        if (isset($field['required']) && $field['required'] && $value==false && (string)$value != '0')
                                $errors[] = sprintf(Tools::displayError('Field "%s" is required.'), $field['label']);
                        elseif($value)
                        {
                            if (!Validate::$field['validation']($value))
                                $errors[] = sprintf(Tools::displayError('Field "%s" is invalid.'), $field['label']);
                        }

                        // Set default value
                        if ($value === false && isset($field['default_value']))
                            $value = $field['default_value'];
                            
                        if(count($errors))
                        {
                            $this->validation_errors = array_merge($this->validation_errors, $errors);
                        }
                        elseif($value==false)
                        {
                            switch($field['validation'])
                            {
                                case 'isUnsignedId':
                                case 'isUnsignedInt':
                                case 'isInt':
                                case 'isBool':
                                    $value = 0;
                                break;
                                default:
                                    $value = '';
                                break;
                            }
                            Configuration::updateValue($field['name'], $value, true);
                        }
                        else
                            Configuration::updateValue($field['name'], $value, true);
                    }

            if(count($this->validation_errors))
                $this->_html .= $this->displayError(implode('<br/>',$this->validation_errors));
            else 
                $this->_html .= Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&conf=6&token='.Tools::getAdminTokenLite('AdminModules'));
        }

        $helper = $this->initForm();
        foreach($this->getDefaults() as $key => $value)
        {
            if(is_array($value))
            {
                foreach ($value as $lang => $val)
                    $helper->fields_value[self::$cfg_prefix.$key][(int)$lang] = Tools::getValue(self::$cfg_prefix.$key.'_'.(int)$lang, Configuration::get(self::$cfg_prefix.$key, (int)$lang));
            }
            else
            {
                $helper->fields_value[self::$cfg_prefix.$key] = Configuration::get(self::$cfg_prefix.$key);
            }
        }

        return $this->_html.$helper->generateForm($this->fields_form);
    }

    protected function initFieldsForm()
    {
        $from = array();

        $from[] = array('name' => $this->l('Recent posts'), 'id' => 'recent');
        $from[] = array('name' => $this->l('Featured posts'), 'id' => 'featured');

        $available_categories = SimpleBlogCategory::getCategories($this->context->language->id, true, false);

        foreach($available_categories as &$category)
        {
            $category['name'] = 'Category: '.$category['name'];
            if($category['is_child'])
                $category['name'] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$category['name'];

            $from[] = array('name' => $category['name'], 'id' => $category['id']);
        }

        $this->fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->displayName,
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Title of the block:'),
                    'name' => self::$cfg_prefix.'TITLE',
                    'lang' => true,
                    'validation' => 'isCleanHtml',
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Display posts from:'),
                    'name' => self::$cfg_prefix.'FROM',
                    'validation' => 'isAnything',
                    'options' => array(
                        'query' => $from,
                        'id' => 'id',
                        'name' => 'name'
                    ),
                ),
                 array(
                    'type' => 'text',
                    'label' => $this->l('Number of posts:'),
                    'name' => self::$cfg_prefix.'NB_POSTS',
                    'validation' => 'isUnsignedInt',
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'button pull-right'
            )
        );
        
    }

    protected function initForm()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        foreach (Language::getLanguages(false) as $lang)
            $helper->languages[] = array(
                'id_lang' => $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => ($default_lang == $lang['id_lang'] ? 1 : 0)
            );

        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'save'.$this->name;
        $helper->toolbar_btn =  array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
            )
        );
        return $helper;
    }

    public function assignModuleVariables()
    {
        foreach($this->getDefaults() as $key => $value)
        {
            if(is_array($value))
                $this->smarty->assign(strtolower($key), Configuration::get(self::$cfg_prefix.$key, $this->context->language->id));
            else
                $this->smarty->assign(strtolower($key), Configuration::get(self::$cfg_prefix.$key));
        }
    }

    public function preparePosts($nb = 6, $from = null)
    {
        $featured = false;

        if($from == 'featured')
        {
            $from = null;
            $featured = true;
        }
        elseif($from == 'recent')
        {
            $from = null;
        }
        else
        {
            $from = (int)$from;
        }

        if(!Module::isInstalled('ph_simpleblog') || !Module::isEnabled('ph_simpleblog'))
            return false;

        require_once _PS_MODULE_DIR_ . 'ph_simpleblog/models/SimpleBlogPost.php';

        $id_lang = $this->context->language->id;

        $posts = SimpleBlogPost::getPosts($id_lang, (int)$nb, $from, null, true, 'sbp.date_add', 'DESC', null, (bool)$featured);

        return $posts;
    }

    public function prepareSimpleBlogRecentPosts()
    {
        $posts = $this->preparePosts(Configuration::get('PH_BLOG_CUSTOM_SECOND_NB_POSTS'), Configuration::get('PH_BLOG_CUSTOM_SECOND_FROM'));

        if(!$posts)
            return;
        
        $id_lang = $this->context->language->id;

        $gridColumns = Configuration::get('PH_BLOG_GRID_COLUMNS');
        $blogLayout = Configuration::get('PH_BLOG_LIST_LAYOUT');

        $this->context->smarty->assign(array(
            'blogLayout' => $blogLayout,
            'columns' => $gridColumns,
            'gallery_dir' => _MODULE_DIR_.'ph_simpleblog/galleries/',
            'custom_block_second_posts' => $posts,
            'tpl_path' => dirname(__FILE__).'/views/templates/hook/',
        ));
    }

    public function hookDisplayHome($params)
    {
        $this->assignModuleVariables();
        $this->prepareSimpleBlogRecentPosts();

        return $this->display(__FILE__, 'custom_block_second.tpl');
    }
}
