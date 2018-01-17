<?php
/**
 * Created by PhpStorm.
 * User: softtt
 * Date: 16.09.2016
 * Time: 16:59
 */

if (!defined('_PS_VERSION_'))
    exit;

class ProductOfTheDay extends ModuleCore
{
    public function __construct()
    {
        $this->name = 'productoftheday';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Vitaliy Sheynin';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = 'Product of the day';
        $this->description = 'Provides product of the day block in a front office .';

        $this->confirmUninstall = 'Are you sure you want to uninstall?';

        if (!Configuration::get('PRODUCTOFTHEDAY_NAME'))
            $this->warning = 'No name provided';
    }

    public function install()
    {
        if (!parent::install() ||
            !$this->registerHook('rightOfHomeSlider')
        ) return false;

        if (file_exists(_PS_MODULE_DIR_.'productoftheday/sql/install.php'))
            include_once (_PS_MODULE_DIR_.'productoftheday/sql/install.php');

        return true;
    }

    public function uninstall()
    {

        if (!parent::uninstall())
            return false;
        if (file_exists(_PS_MODULE_DIR_.'productoftheday/sql/uninstall.php'))
            include_once (_PS_MODULE_DIR_.'productoftheday/sql/uninstall.php');
        return true;
    }


    public function hookRightOfHomeSlider($params)
    {
        $product = $this->getRandomProductOfTheDay($params);

        $this->smarty->assign([
            'product' => [$product],
            'disable' => $product ? false : true,
        ]);

        return $this->display(__FILE__, 'productoftheday.tpl');
    }


    public function getRandomProductOfTheDay($params)
    {
        $id_lang = (int)$params['cookie']->id_lang;

        $sql = 'SELECT p.*, product_shop.*, stock.`out_of_stock` out_of_stock, pl.`description`, pl.`description_short`,
						pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`,
						p.`ean13`, p.`upc`,'.'i.`id_image`, il.`legend`'.'
					FROM `'._DB_PREFIX_.'product` p
					LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
						p.`id_product` = pl.`id_product`
						AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').'
					)
					'.Shop::addSqlAssociation('product', 'p').'
					LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product`)
					LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)$id_lang.')
					'.Product::sqlStock('p', 0).'
					WHERE p.is_product_of_the_day = '.'1'.'
					ORDER BY RAND()
					LIMIT 1';

        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (is_array($rows) && count($rows)) {
            $row = $rows[0];
        } else {
            return false;
        }

        return Product::getProductProperties($id_lang, $row);
    }

}
