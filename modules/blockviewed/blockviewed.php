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

if (!defined('_PS_VERSION_'))
	exit;

class BlockViewed extends Module
{
    protected static $cache_viewed;

	public function __construct()
	{
		$this->name = 'blockviewed';
		$this->tab = 'front_office_features';
		$this->version = '1.2.3';
		$this->author = 'PrestaShop';
		$this->need_instance = 0;

		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Viewed products block');
		$this->description = $this->l('Adds a block displaying recently viewed products.');
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
	}

	public function install()
	{
		return (parent::install() && $this->registerHook('header') && $this->registerHook('displayHome') && Configuration::updateValue('PRODUCTS_VIEWED_NBR', 10));

	}

	public function getContent()
	{
		$output = '';
		if (Tools::isSubmit('submitBlockViewed'))
		{
			if (!($productNbr = Tools::getValue('PRODUCTS_VIEWED_NBR')) || empty($productNbr))
				$output .= $this->displayError($this->l('You must fill in the \'Products displayed\' field.'));
			elseif ((int)($productNbr) == 0)
				$output .= $this->displayError($this->l('Invalid number.'));
			else
			{
				Configuration::updateValue('PRODUCTS_VIEWED_NBR', (int)$productNbr);
				$output .= $this->displayConfirmation($this->l('Settings updated.'));
			}
		}
		return $output.$this->renderForm();
	}


    public function getViewedProds($productsViewed,$params)
    {
        $defaultCover = Language::getIsoById($params['cookie']->id_lang).'-default';

        $productIds = implode(',', array_map('intval', $productsViewed));
        $productsImages = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT MAX(image_shop.id_image) id_image, p.id_product, il.legend, product_shop.active, pl.name, pl.description_short, pl.link_rewrite, cl.link_rewrite AS category_rewrite
			FROM '._DB_PREFIX_.'product p
			'.Shop::addSqlAssociation('product', 'p').'
			LEFT JOIN '._DB_PREFIX_.'product_lang pl ON (pl.id_product = p.id_product'.Shop::addSqlRestrictionOnLang('pl').')
			LEFT JOIN '._DB_PREFIX_.'image i ON (i.id_product = p.id_product)'.
            Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
			LEFT JOIN '._DB_PREFIX_.'image_lang il ON (il.id_image = image_shop.id_image AND il.id_lang = '.(int)($params['cookie']->id_lang).')
			LEFT JOIN '._DB_PREFIX_.'category_lang cl ON (cl.id_category = product_shop.id_category_default'.Shop::addSqlRestrictionOnLang('cl').')
			WHERE p.id_product IN ('.$productIds.')
			AND pl.id_lang = '.(int)($params['cookie']->id_lang).'
			AND cl.id_lang = '.(int)($params['cookie']->id_lang).'
			GROUP BY product_shop.id_product'
        );

        $productsImagesArray = array();
        foreach ($productsImages as $pi)
            $productsImagesArray[$pi['id_product']] = $pi;

        $productsViewedObj = array();
        foreach ($productsViewed as $productViewed)
        {
            $obj = (object)'Product';
            if (!isset($productsImagesArray[$productViewed]) || (!$obj->active = $productsImagesArray[$productViewed]['active']))
                continue;
            else
            {
                $obj->id = (int)($productsImagesArray[$productViewed]['id_product']);
                $obj->id_image = (int)$productsImagesArray[$productViewed]['id_image'];
                $obj->cover = (int)($productsImagesArray[$productViewed]['id_product']).'-'.(int)($productsImagesArray[$productViewed]['id_image']);
                $obj->legend = $productsImagesArray[$productViewed]['legend'];
                $obj->name = $productsImagesArray[$productViewed]['name'];
                $obj->description_short = $productsImagesArray[$productViewed]['description_short'];
                $obj->link_rewrite = $productsImagesArray[$productViewed]['link_rewrite'];
                $obj->category_rewrite = $productsImagesArray[$productViewed]['category_rewrite'];
                // $obj is not a real product so it cannot be used as argument for getProductLink()
                $obj->product_link = $this->context->link->getProductLink($obj->id, $obj->link_rewrite, $obj->category_rewrite);

                if (!isset($obj->cover) || !$productsImagesArray[$productViewed]['id_image'])
                {
                    $obj->cover = $defaultCover;
                    $obj->legend = '';
                }
                $productsViewedObj[] = $obj;
            }
        }
        return $productsViewedObj;
    }
	public function hookRightColumn($params)
	{
		$productsViewed = (isset($params['cookie']->viewed) && !empty($params['cookie']->viewed)) ? array_slice(array_reverse(explode(',', $params['cookie']->viewed)), 0, Configuration::get('PRODUCTS_VIEWED_NBR')) : array();

		if (count($productsViewed))
		{
			$productsViewedObj = $this->getViewedProds($productsViewed,$params);

			if (!count($productsViewedObj))
				return;

			$this->smarty->assign(array(
				'productsViewedObj' => $productsViewedObj,
				'mediumSize' => Image::getSize('medium')));
			return $this->display(__FILE__, 'blockviewed.tpl');
		}
		return;
	}

	public function hookLeftColumn($params)
	{
		return $this->hookRightColumn($params);
	}

    public static function getViewedProductForHome($id_lang,$id_product)
    {
        $sql = 'SELECT p.*, product_shop.*, stock.`out_of_stock` out_of_stock, pl.`description`, pl.`description_short`,
						pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`,
						p.`ean13`, p.`upc`, MAX(image_shop.`id_image`) id_image, il.`legend`,
						DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
						INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
							DAY)) > 0 AS new
					FROM `'._DB_PREFIX_.'product` p
					LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
						p.`id_product` = pl.`id_product`
						AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').'
					)
					'.Shop::addSqlAssociation('product', 'p').'
					LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product`)'.
            Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
					LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)$id_lang.')
					'.Product::sqlStock('p', 0).'
					WHERE p.id_product = '.(int)$id_product.'
					GROUP BY product_shop.id_product';

        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
        if (!$row)
            return false;

        return Product::getProductProperties($id_lang, $row);
    }

    public static function setViewedNumber($number)
    {
        $id_shop = Shop::getContextShopID(true);
        $id_shop_group = Shop::getContextShopGroupID(true);
        Configuration::set('PRODUCTS_VIEWED_NBR',$number,$id_shop_group,$id_shop);
    }

    public function hookDisplayHome($params)
    {
        $this->setViewedNumber(10);

        $productsViewed = (isset($params['cookie']->viewed) && !empty($params['cookie']->viewed)) ? array_slice(array_reverse(explode(',', $params['cookie']->viewed)), 0, Configuration::get('PRODUCTS_VIEWED_NBR')) : array();
        BlockViewed::$cache_viewed = [];
        foreach($productsViewed as $id)
        {
            $prodsObj = $this->getViewedProductForHome((int)$params['cookie']->id_lang,$id);
            array_push(BlockViewed::$cache_viewed,$prodsObj);
        }



        if (BlockViewed::$cache_viewed === false)
            return false;

        if (!$this->isCached('blockviewed-home.tpl', $this->getCacheId('blockviewed-home')))
        {
            $this->smarty->assign(array(
                'viewedProducts' => BlockViewed::$cache_viewed,
                'mediumSize' => Image::getSize(ImageType::getFormatedName('medium')),
                'homeSize' => Image::getSize(ImageType::getFormatedName('home'))
            ));
        }
        return $this->display(__FILE__, 'blockviewed-home.tpl', $this->getCacheId('blockviewed-home'));
    }

	public function hookFooter($params)
	{
		return $this->hookRightColumn($params);
	}

	public function hookHeader($params)
	{
		$id_product = (int)Tools::getValue('id_product');
		$productsViewed = (isset($params['cookie']->viewed) && !empty($params['cookie']->viewed)) ? array_slice(array_reverse(explode(',', $params['cookie']->viewed)), 0, Configuration::get('PRODUCTS_VIEWED_NBR')) : array();

		if ($id_product && !in_array($id_product, $productsViewed))
		{
			$product = new Product((int)$id_product);
			if ($product->checkAccess((int)$this->context->customer->id))
			{
				if (isset($params['cookie']->viewed) && !empty($params['cookie']->viewed))
					$params['cookie']->viewed .= ','.(int)$id_product;
				else
					$params['cookie']->viewed = (int)$id_product;
			}
		}
		$this->context->controller->addCSS(($this->_path).'blockviewed.css', 'all');
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
						'label' => $this->l('Products to display'),
						'name' => 'PRODUCTS_VIEWED_NBR',
						'class' => 'fixed-width-xs',
						'desc' => $this->l('Define the number of products displayed in this block.')
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table =  $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitBlockViewed';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues()
	{
		return array(
			'PRODUCTS_VIEWED_NBR' => Tools::getValue('PRODUCTS_VIEWED_NBR', Configuration::get('PRODUCTS_VIEWED_NBR')),
		);
	}

    protected function getCacheId($name = null)
    {
        if ($name === null)
            $name = 'blocknewproducts';
        return parent::getCacheId($name.'|'.date('Ymd'));
    }
}
