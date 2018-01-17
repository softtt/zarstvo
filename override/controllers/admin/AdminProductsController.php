<?php
/**
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2014 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class AdminProductsController extends AdminProductsControllerCore
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'product';
        $this->className = 'Product';
        $this->lang = true;
        $this->explicitSelect = true;
        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            )
        );
        if (!Tools::getValue('id_product'))
            $this->multishop_context_group = false;

        AdminController::__construct();

        $this->imageType = 'jpg';
        $this->_defaultOrderBy = 'position';
        $this->max_file_size = (int)(Configuration::get('PS_LIMIT_UPLOAD_FILE_VALUE') * 1000000);
        $this->max_image_size = (int)Configuration::get('PS_PRODUCT_PICTURE_MAX_SIZE');
        $this->allow_export = true;

        // @since 1.5 : translations for tabs
        $this->available_tabs_lang = array(
            'Informations' => $this->l('Information'),
            'Pack' => $this->l('Pack'),
            'VirtualProduct' => $this->l('Virtual Product'),
            'Prices' => $this->l('Prices'),
            'Seo' => $this->l('SEO'),
            'Images' => $this->l('Images'),
            'Associations' => $this->l('Associations'),
            'Shipping' => $this->l('Shipping'),
            'Combinations' => $this->l('Combinations'),
            'Features' => $this->l('Features'),
            'Customization' => $this->l('Customization'),
            'Attachments' => $this->l('Attachments'),
            'Quantities' => $this->l('Quantities'),
            'Suppliers' => $this->l('Suppliers'),
            'Warehouses' => $this->l('Warehouses'),
        );

        $this->available_tabs = array('Quantities' => 6, 'Warehouses' => 14);
        if ($this->context->shop->getContext() != Shop::CONTEXT_GROUP)
            $this->available_tabs = array_merge($this->available_tabs, array(
                'Informations' => 0,
                'Pack' => 7,
//                'VirtualProduct' => 8,
                'Prices' => 1,
                'Seo' => 2,
                'Associations' => 3,
                'Images' => 9,
                'Shipping' => 4,
                'Combinations' => 5,
                'Features' => 10,
                // 'Customization' => 11,
                // 'Attachments' => 12,
                // 'Suppliers' => 13,
            ));

        // Sort the tabs that need to be preloaded by their priority number
        asort($this->available_tabs, SORT_NUMERIC);

        /* Adding tab if modules are hooked */
        $modules_list = Hook::getHookModuleExecList('displayAdminProductsExtra');
        if (is_array($modules_list) && count($modules_list) > 0)
            foreach ($modules_list as $m)
            {
                $this->available_tabs['Module'.ucfirst($m['module'])] = 23;
                $this->available_tabs_lang['Module'.ucfirst($m['module'])] = Module::getModuleName($m['module']);
            }

        if (Tools::getValue('reset_filter_category'))
            $this->context->cookie->id_category_products_filter = false;
        if (Shop::isFeatureActive() && $this->context->cookie->id_category_products_filter)
        {
            $category = new Category((int)$this->context->cookie->id_category_products_filter);
            if (!$category->inShop())
            {
                $this->context->cookie->id_category_products_filter = false;
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminProducts'));
            }
        }
        /* Join categories table */
        if ($id_category = (int)Tools::getValue('productFilter_cl!name'))
        {
            $this->_category = new Category((int)$id_category);
            $_POST['productFilter_cl!name'] = $this->_category->name[$this->context->language->id];
        }
        else
        {
            if ($id_category = (int)Tools::getValue('id_category'))
            {
                $this->id_current_category = $id_category;
                $this->context->cookie->id_category_products_filter = $id_category;
            }
            elseif ($id_category = $this->context->cookie->id_category_products_filter)
                $this->id_current_category = $id_category;
            if ($this->id_current_category)
                $this->_category = new Category((int)$this->id_current_category);
            else
                $this->_category = new Category();
        }

        $join_category = false;
        if (Validate::isLoadedObject($this->_category) && empty($this->_filter))
            $join_category = true;

        $this->_join .= '
        LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = a.`id_product`)
        LEFT JOIN `'._DB_PREFIX_.'stock_available` sav ON (sav.`id_product` = a.`id_product` AND sav.`id_product_attribute` = 0
        '.StockAvailable::addSqlShopRestriction(null, null, 'sav').') ';

        $alias = 'sa';
        $alias_image = 'image_shop';

        $id_shop = Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP? (int)$this->context->shop->id : 'a.id_shop_default';
        $this->_join .= ' JOIN `'._DB_PREFIX_.'product_shop` sa ON (a.`id_product` = sa.`id_product` AND sa.id_shop = '.$id_shop.')
                LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON ('.$alias.'.`id_category_default` = cl.`id_category` AND b.`id_lang` = cl.`id_lang` AND cl.id_shop = '.$id_shop.')
                LEFT JOIN `'._DB_PREFIX_.'shop` shop ON (shop.id_shop = '.$id_shop.')
                LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop ON (image_shop.`id_image` = i.`id_image` AND image_shop.`cover` = 1 AND image_shop.id_shop = '.$id_shop.')
                LEFT JOIN `'._DB_PREFIX_.'product_download` pd ON (pd.`id_product` = a.`id_product`)';

        $this->_select .= 'shop.name as shopname, a.id_shop_default, ';
        $this->_select .= 'MAX('.$alias_image.'.id_image) id_image, cl.name `name_category`, '.$alias.'.`price`, 0 AS price_final, a.`is_virtual`, pd.`nb_downloadable`, sav.`quantity` as sav_quantity, '.$alias.'.`active`, '.$alias.'.`show_new`, '.$alias.'.`show_bestsales`, IF(sav.`quantity`<=0, 1, 0) badge_danger';

        if ($join_category)
        {
            $this->_join .= ' INNER JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_product` = a.`id_product` AND cp.`id_category` = '.(int)$this->_category->id.') ';
            $this->_select .= ' , cp.`position`, ';
        }

        $this->_group = 'GROUP BY '.$alias.'.id_product';

        $this->fields_list = array();
        $this->fields_list['id_product'] = array(
            'title' => $this->l('ID'),
            'align' => 'center',
            'class' => 'fixed-width-xs',
            'type' => 'int'
        );
        $this->fields_list['image'] = array(
            'title' => $this->l('Image'),
            'align' => 'center',
            'image' => 'p',
            'orderby' => false,
            'filter' => false,
            'search' => false
        );
        $this->fields_list['name'] = array(
            'title' => $this->l('Name'),
            'filter_key' => 'b!name'
        );
        $this->fields_list['reference'] = array(
            'title' => $this->l('Reference'),
            'align' => 'left',
        );

        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP)
            $this->fields_list['shopname'] = array(
                'title' => $this->l('Default shop'),
                'filter_key' => 'shop!name',
            );
        else
            $this->fields_list['name_category'] = array(
                'title' => $this->l('Category'),
                'filter_key' => 'cl!name',
            );
        $this->fields_list['price'] = array(
            'title' => $this->l('Base price'),
            'type' => 'price',
            'align' => 'text-right',
            'filter_key' => 'a!price'
        );
        $this->fields_list['price_final'] = array(
            'title' => $this->l('Final price'),
            'type' => 'price',
            'align' => 'text-right',
            'havingFilter' => true,
            'orderby' => false,
            'search' => false
        );

        if (Configuration::get('PS_STOCK_MANAGEMENT'))
            $this->fields_list['sav_quantity'] = array(
                'title' => $this->l('Quantity'),
                'type' => 'int',
                'align' => 'text-right',
                'filter_key' => 'sav!quantity',
                'orderby' => true,
                'badge_danger' => true,
                //'hint' => $this->l('This is the quantity available in the current shop/group.'),
            );

        $this->fields_list['active'] = array(
            'title' => $this->l('Status'),
            'active' => 'status',
            'filter_key' => $alias.'!active',
            'align' => 'text-center',
            'type' => 'bool',
            'class' => 'fixed-width-sm',
            'orderby' => false
        );

        $this->fields_list['show_new'] = array(
            'title' => $this->l('Новинка'),
            'active' => 'show_new',
            'filter_key' => $alias.'!show_new',
            'align' => 'text-center',
            'type' => 'bool',
            'class' => 'fixed-width-sm',
            'orderby' => false
        );

        $this->fields_list['show_bestsales'] = array(
            'title' => $this->l('Хит продаж'),
            'active' => 'show_bestsales',
            'filter_key' => $alias.'!show_bestsales',
            'align' => 'text-center',
            'type' => 'bool',
            'class' => 'fixed-width-sm',
            'orderby' => false
        );

        $this->fields_list['is_product_of_the_day'] = array(
            'title' => "Товар дня",
            'active' => 'is_product_of_the_day',
            'filter_key' => $alias.'!is_product_of_the_day',
            'align' => 'text-center',
            'type' => 'bool',
            'class' => 'fixed-width-sm',
            'orderby' => false
        );

        if ($join_category && (int)$this->id_current_category)
            $this->fields_list['position'] = array(
                'title' => $this->l('Position'),
                'filter_key' => 'cp!position',
                'align' => 'center',
                'position' => 'position'
            );
    }

    public function initProcess()
    {
        if (Tools::isSubmit('submitAddproductAndStay') || Tools::isSubmit('submitAddproduct'))
        {
            $this->id_object = (int)Tools::getValue('id_product');
            $this->object = new Product($this->id_object);

            if ($this->isTabSubmitted('Informations') && $this->object->is_virtual && (int)Tools::getValue('type_product') != 2)
            {
                if ($id_product_download = (int)ProductDownload::getIdFromIdProduct($this->id_object))
                {
                    $product_download = new ProductDownload($id_product_download);
                    if (!$product_download->deleteFile($id_product_download))
                        $this->errors[] = Tools::displayError('Cannot delete file');
                }

            }
        }

        // Delete a product in the download folder
        if (Tools::getValue('deleteVirtualProduct'))
        {
            if ($this->tabAccess['delete'] === '1')
                $this->action = 'deleteVirtualProduct';
            else
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
        }
        // Product preview
        elseif (Tools::isSubmit('submitAddProductAndPreview'))
        {
            $this->display = 'edit';
            $this->action = 'save';
            if (Tools::getValue('id_product'))
            {
                $this->id_object = Tools::getValue('id_product');
                $this->object = new Product((int)Tools::getValue('id_product'));
            }
        }
        elseif (Tools::isSubmit('submitAttachments'))
        {
            if ($this->tabAccess['edit'] === '1')
            {
                $this->action = 'attachments';
                $this->tab_display = 'attachments';
            }
            else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        }
        // Product duplication
        elseif (Tools::getIsset('duplicate'.$this->table))
        {
            if ($this->tabAccess['add'] === '1')
                $this->action = 'duplicate';
            else
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
        }
        // Product images management
        elseif (Tools::getValue('id_image') && Tools::getValue('ajax'))
        {
            if ($this->tabAccess['edit'] === '1')
                $this->action = 'image';
            else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        }
        // Product attributes management
        elseif (Tools::isSubmit('submitProductAttribute'))
        {
            if ($this->tabAccess['edit'] === '1')
                $this->action = 'productAttribute';
            else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        }
        // Product features management
        elseif (Tools::isSubmit('submitFeatures') || Tools::isSubmit('submitFeaturesAndStay'))
        {
            if ($this->tabAccess['edit'] === '1')
                $this->action = 'features';
            else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        }
        // Product specific prices management NEVER USED
        elseif (Tools::isSubmit('submitPricesModification'))
        {
            if ($this->tabAccess['add'] === '1')
                $this->action = 'pricesModification';
            else
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
        }
        elseif (Tools::isSubmit('deleteSpecificPrice'))
        {
            if ($this->tabAccess['delete'] === '1')
                $this->action = 'deleteSpecificPrice';
            else
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
        }
        elseif (Tools::isSubmit('submitSpecificPricePriorities'))
        {
            if ($this->tabAccess['edit'] === '1')
            {
                $this->action = 'specificPricePriorities';
                $this->tab_display = 'prices';
            }
            else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        }
        // Customization management
        elseif (Tools::isSubmit('submitCustomizationConfiguration'))
        {
            if ($this->tabAccess['edit'] === '1')
            {
                $this->action = 'customizationConfiguration';
                $this->tab_display = 'customization';
                $this->display = 'edit';
            }
            else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        }
        elseif (Tools::isSubmit('submitProductCustomization'))
        {
            if ($this->tabAccess['edit'] === '1')
            {
                $this->action = 'productCustomization';
                $this->tab_display = 'customization';
                $this->display = 'edit';
            }
            else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        }
        elseif (Tools::isSubmit('import'))
        {
            $this->importProducts();
        }

        if (!$this->action)
            AdminController::initProcess();
        else
            $this->id_object = (int)Tools::getValue($this->identifier);

        if (isset($this->available_tabs[Tools::getValue('key_tab')]))
            $this->tab_display = Tools::getValue('key_tab');

        // Set tab to display if not decided already
        if (!$this->tab_display && $this->action)
            if (in_array($this->action, array_keys($this->available_tabs)))
                $this->tab_display = $this->action;

        // And if still not set, use default
        if (!$this->tab_display)
        {
            if (in_array($this->default_tab, $this->available_tabs))
                $this->tab_display = $this->default_tab;
            else
                $this->tab_display = key($this->available_tabs);
        }
    }
    public function initFormInformations($product)
    {
        if (!$this->default_form_language)
            $this->getLanguages();

        $data = $this->createTemplate($this->tpl_form);

        $currency = $this->context->currency;
        $data->assign('languages', $this->_languages);
        $data->assign('default_form_language', $this->default_form_language);
        $data->assign('currency', $currency);
        $this->object = $product;
        //$this->display = 'edit';
        $data->assign('product_name_redirected', Product::getProductName((int)$product->id_product_redirected, null, (int)$this->context->language->id));
        /*
        * Form for adding a virtual product like software, mp3, etc...
        */
        $product_download = new ProductDownload();
        if ($id_product_download = $product_download->getIdFromIdProduct($this->getFieldValue($product, 'id')))
            $product_download = new ProductDownload($id_product_download);

        $product->{'productDownload'} = $product_download;

        $product_props = array();
        // global informations
        array_push($product_props, 'reference', 'ean13', 'upc',
        'available_for_order', 'show_price', 'online_only',
        'id_manufacturer'
        );

        // specific / detailled information
        array_push($product_props,
        // physical product
        'width', 'height', 'weight', 'active', 'show_new', 'show_bestsales','is_product_of_the_day',
        // virtual product
        'is_virtual', 'cache_default_attribute',
        // customization
        'uploadable_files', 'text_fields'
        );
        // prices
        array_push($product_props,
            'price', 'wholesale_price', 'id_tax_rules_group', 'unit_price_ratio', 'on_sale',
            'unity', 'minimum_quantity', 'additional_shipping_cost',
            'available_now', 'available_later', 'available_date'
        );

        if (Configuration::get('PS_USE_ECOTAX'))
            array_push($product_props, 'ecotax');

        foreach ($product_props as $prop)
            $product->$prop = $this->getFieldValue($product, $prop);

        $product->name['class'] = 'updateCurrentText';
        if (!$product->id || Configuration::get('PS_FORCE_FRIENDLY_PRODUCT'))
            $product->name['class'] .= ' copy2friendlyUrl';

        $images = Image::getImages($this->context->language->id, $product->id);

        if (is_array($images))
        {
            foreach ($images as $k => $image)
                $images[$k]['src'] = $this->context->link->getImageLink($product->link_rewrite[$this->context->language->id], $product->id.'-'.$image['id_image'], 'small_default');
            $data->assign('images', $images);
        }
        $data->assign('imagesTypes', ImageType::getImagesTypes('products'));

        $product->tags = Tag::getProductTags($product->id);

        $data->assign('product_type', (int)Tools::getValue('type_product', $product->getType()));
        $data->assign('is_in_pack', (int)Pack::isPacked($product->id));

        $check_product_association_ajax = false;
        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_ALL)
            $check_product_association_ajax = true;

        // TinyMCE
        $iso_tiny_mce = $this->context->language->iso_code;
        $iso_tiny_mce = (file_exists(_PS_ROOT_DIR_.'/js/tiny_mce/langs/'.$iso_tiny_mce.'.js') ? $iso_tiny_mce : 'en');
        $data->assign('ad', dirname($_SERVER['PHP_SELF']));
        $data->assign('iso_tiny_mce', $iso_tiny_mce);
        $data->assign('check_product_association_ajax', $check_product_association_ajax);
        $data->assign('id_lang', $this->context->language->id);
        $data->assign('product', $product);
        $data->assign('token', $this->token);
        $data->assign('currency', $currency);
        $data->assign($this->tpl_form_vars);
        $data->assign('link', $this->context->link);
        $data->assign('PS_PRODUCT_SHORT_DESC_LIMIT', Configuration::get('PS_PRODUCT_SHORT_DESC_LIMIT') ? Configuration::get('PS_PRODUCT_SHORT_DESC_LIMIT') : 400);
        $this->tpl_form_vars['product'] = $product;
        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }

    /*
    * module: advancedfeaturesvalues
    * date: 2015-06-03 10:41:04
    * version: 1.0.7
    */
    public function initFormFeatures($obj)
    {
        if (!$this->default_form_language)
            $this->getLanguages();

        $tpl_path = _PS_MODULE_DIR_.'advancedfeaturesvalues/views/templates/admin/products/features.tpl';
        $data = $this->context->smarty->createTemplate($tpl_path, $this->context->smarty);

        $data->assign('default_form_language', $this->default_form_language);
        $data->assign('languages', $this->_languages);

        if (!Feature::isFeatureActive())
            $this->displayWarning($this->l('This feature has been disabled. ').'
                <a href="index.php?tab=AdminPerformance&token='.Tools::getAdminTokenLite('AdminPerformance').'#featuresDetachables">'.
                $this->l('Performances').'</a>');
        else
        {
            if ($obj->id)
            {
                if ($this->product_exists_in_shop)
                {
                    $features = Feature::getFeatures($this->context->language->id, (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP));

                    foreach ($features as $k => $tab_features)
                    {
                        $features[$k]['current_item'] = array();
                        $features[$k]['val'] = array();

                        $custom = true;
                        foreach ($obj->getFeatures() as $tab_products)
                            if ($tab_products['id_feature'] == $tab_features['id_feature'])
                                $features[$k]['current_item'][] = $tab_products['id_feature_value'];

                        $features[$k]['featureValues'] = FeatureValue::getFeatureValuesWithLang($this->context->language->id, (int)$tab_features['id_feature']);
                        if (count($features[$k]['featureValues']))
                            foreach ($features[$k]['featureValues'] as $value)
                                if (in_array($value['id_feature_value'], $features[$k]['current_item']))
                                    $custom = false;

                        if ($custom && !empty($features[$k]['current_item']))
                            $features[$k]['val'] = FeatureValue::getFeatureValueLang($features[$k]['current_item'][0]);
                    }

                    $data->assign('available_features', $features);
                    $data->assign('product', $obj);
                    $data->assign('link', $this->context->link);
                    $data->assign('default_form_language', $this->default_form_language);
                }
                else
                    $this->displayWarning($this->l('You must save the product in this shop before adding features.'));
            }
            else
                $this->displayWarning($this->l('You must save this product before adding features.'));
        }
        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }


    /*
    * module: advancedfeaturesvalues
    * date: 2015-06-03 10:41:04
    * version: 1.0.7
    */
    public function processFeatures()
    {
        if (!Feature::isFeatureActive())
            return;

        if (Validate::isLoadedObject($product = new Product((int)Tools::getValue('id_product'))))
        {
            // delete all objects
            $product->deleteFeatures();

            // add new objects
            $languages = Language::getLanguages(false);
            foreach ($_POST as $key => $val)
            {
                if (preg_match('/^feature_([0-9]+)_value/i', $key, $match))
                {
                    if (!empty($val))
                    {
                        foreach ($val as $v)
                            $product->addFeaturesToDB($match[1], $v);
                    }
                    else
                    {
                        if ($default_value = $this->checkFeatures($languages, $match[1]))
                        {
                            $id_value = $product->addFeaturesToDB($match[1], 0, 1);
                            foreach ($languages as $language)
                            {
                                if ($cust = Tools::getValue('custom_'.$match[1].'_'.(int)$language['id_lang']))
                                    $product->addFeaturesCustomToDB($id_value, (int)$language['id_lang'], $cust);
                                else
                                    $product->addFeaturesCustomToDB($id_value, (int)$language['id_lang'], $default_value);
                            }
                        }
                    }
                }
            }
        }
        else
            $this->errors[] = Tools::displayError('A product must be created before adding features.');
    }


    /**
     * postProcess handle every checks before saving products information
     *
     * @return void
     */
    public function postProcess()
    {
        if (Tools::isSubmit('show_new'.$this->table)) {
            $product = new Product($this->id_object);
            $product->show_new = $product->show_new ? 0 : 1;
            $product->update();
            Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);
        }

        if (Tools::isSubmit('show_bestsales'.$this->table)) {
            $product = new Product($this->id_object);
            $product->show_bestsales = $product->show_bestsales ? 0 : 1;
            $product->update();
            Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);
        }
        if (Tools::isSubmit('is_product_of_the_day'.$this->table)) {
            $product = new Product($this->id_object);
            $product->is_product_of_the_day = $product->is_product_of_the_day ? 0 : 1;
            $product->update();
            Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);
        }

        if (!$this->redirect_after)
            AdminController::postProcess();

        if ($this->display == 'edit' || $this->display == 'add')
        {
            $this->addJqueryUI(array(
                'ui.core',
                'ui.widget'
            ));

            $this->addjQueryPlugin(array(
                'autocomplete',
                'tablednd',
                'thickbox',
                'ajaxfileupload',
                'date',
                'tagify',
                'select2',
                'validate'
            ));

            $this->addJS(array(
                _PS_JS_DIR_.'admin-products.js',
                _PS_JS_DIR_.'attributesBack.js',
                _PS_JS_DIR_.'price.js',
                _PS_JS_DIR_.'tiny_mce/tiny_mce.js',
                _PS_JS_DIR_.'tinymce.inc.js',
                _PS_JS_DIR_.'admin-dnd.js',
                _PS_JS_DIR_.'jquery/ui/jquery.ui.progressbar.min.js',
                _PS_JS_DIR_.'vendor/spin.js',
                _PS_JS_DIR_.'vendor/ladda.js'
            ));

            $this->addJS(_PS_JS_DIR_.'jquery/plugins/select2/select2_locale_'.$this->context->language->iso_code.'.js');
            $this->addJS(_PS_JS_DIR_.'jquery/plugins/validate/localization/messages_'.$this->context->language->iso_code.'.js');

            $this->addCSS(array(
                _PS_JS_DIR_.'jquery/plugins/timepicker/jquery-ui-timepicker-addon.css'
            ));
        }
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display))
            $this->page_header_toolbar_btn['import'] = array(
                    'href' => self::$currentIndex.'&import&token='.$this->token,
                    'desc' => $this->l('Import products prices and quantity', null, null, false),
                    'icon' => 'process-icon-download'
                );
            $this->page_header_toolbar_btn['new_product'] = array(
                    'href' => self::$currentIndex.'&addproduct&token='.$this->token,
                    'desc' => $this->l('Add new product', null, null, false),
                    'icon' => 'process-icon-new'
                );
        if ($this->display == 'edit')
        {
            if (($product = $this->loadObject(true)) && $product->isAssociatedToShop())
            {
                // adding button for preview this product
                if ($url_preview = $this->getPreviewUrl($product))
                    $this->page_header_toolbar_btn['preview'] = array(
                        'short' => $this->l('Preview', null, null, false),
                        'href' => $url_preview,
                        'desc' => $this->l('Preview', null, null, false),
                        'target' => true,
                        'class' => 'previewUrl'
                    );

                // adding button for duplicate this product
                if ($this->tabAccess['add'])
                    $this->page_header_toolbar_btn['duplicate'] = array(
                        'short' => $this->l('Duplicate', null, null, false),
                        'href' => $this->context->link->getAdminLink('AdminProducts', true).'&id_product='.(int)$product->id.'&duplicateproduct',
                        'desc' => $this->l('Duplicate', null, null, false),
                        'confirm' => 1,
                        'js' => 'if (confirm(\''.$this->l('Also copy images', null, true, false).' ?\')){document.location.href = \''.Tools::safeOutput($this->context->link->getAdminLink('AdminProducts', true).'&id_product='.(int)$product->id.'&duplicateproduct').'\'; return false;} else{document.location.href = \''.Tools::safeOutput($this->context->link->getAdminLink('AdminProducts', true).'&id_product='.(int)$product->id.'&duplicateproduct&noimage=1').'\'; return false;}'
                    );

                // adding button for preview this product statistics
                if (file_exists(_PS_MODULE_DIR_.'statsproduct/statsproduct.php'))
                    $this->page_header_toolbar_btn['stats'] = array(
                    'short' => $this->l('Statistics', null, null, false),
                    'href' => $this->context->link->getAdminLink('AdminStats').'&module=statsproduct&id_product='.(int)$product->id,
                    'desc' => $this->l('Product sales', null, null, false),
                );

                // adding button for delete this product
                if ($this->tabAccess['delete'])
                    $this->page_header_toolbar_btn['delete'] = array(
                        'short' => $this->l('Delete', null, null, false),
                        'href' => $this->context->link->getAdminLink('AdminProducts').'&id_product='.(int)$product->id.'&deleteproduct',
                        'desc' => $this->l('Delete this product', null, null, false),
                        'confirm' => 1,
                        'js' => 'if (confirm(\''.$this->l('Delete product?', null, true, false).'\')){return true;}else{event.preventDefault();}'
                    );
            }
        }
        AdminController::initPageHeaderToolbar();
    }

    public function importProducts()
    {
        $import = new Import();
        $import_filename = '';
        $import_file = '';
        foreach (glob(__DIR__ . '/../../../export_import/import/*.csv') as $file) {
            $filename = substr($file, strrpos($file, '/') + 1);
            if ($filename > $import_filename || $import_filename = '') {
                $import_file = $file;
                $import_filename = $filename;
            } else {
                rename($file, __DIR__ . '/../../../export_import/import/DELETED/'.$filename);
            }
        }
        if ($import_filename) {
            $import->importProducts($import_file);
            rename($file, __DIR__ . '/../../../export_import/import/IMPORTED/'.$import_filename);
            $import->writeLogToFile();
        }
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminProducts'));
    }
}
