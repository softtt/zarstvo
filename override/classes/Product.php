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

class Product extends ProductCore
{
    /** @var boolean Show product in new block */
    public $show_new = false;

    /** @var boolean Show product in bestsales block */
    public $show_bestsales = false;

    /** @var boolean Show product in POTD block */
    public $is_product_of_the_day = false;

    /** @var text Product recipes */
    public $recipes;

    public static $definition = array(
        'table' => 'product',
        'primary' => 'id_product',
        'multilang' => true,
        'multilang_shop' => true,
        'fields' => array(
            // Classic fields
            'id_shop_default' =>            array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_manufacturer' =>            array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_supplier' =>                array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'reference' =>                  array('type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 32),
            'supplier_reference' =>         array('type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 32),
            'location' =>                   array('type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 64),
            'width' =>                      array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
            'height' =>                     array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
            'depth' =>                      array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
            'weight' =>                     array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
            'quantity_discount' =>          array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'ean13' =>                      array('type' => self::TYPE_STRING, 'validate' => 'isEan13', 'size' => 13),
            'upc' =>                        array('type' => self::TYPE_STRING, 'validate' => 'isUpc', 'size' => 12),
            'cache_is_pack' =>              array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'cache_has_attachments' =>      array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'is_virtual' =>                 array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),

            /* Shop fields */
            'id_category_default' =>        array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId'),
            'id_tax_rules_group' =>         array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId'),
            'on_sale' =>                    array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'online_only' =>                array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'ecotax' =>                     array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isPrice'),
            'minimal_quantity' =>           array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'),
            'price' =>                      array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isPrice', 'required' => true),
            'wholesale_price' =>            array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isPrice'),
            'unity' =>                      array('type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isString'),
            'unit_price_ratio' =>           array('type' => self::TYPE_FLOAT, 'shop' => true),
            'additional_shipping_cost' =>   array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isPrice'),
            'customizable' =>               array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'),
            'text_fields' =>                array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'),
            'uploadable_files' =>           array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'),
            'active' =>                     array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'redirect_type' =>              array('type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isString'),
            'id_product_redirected' =>      array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId'),
            'available_for_order' =>        array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'available_date' =>             array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDateFormat'),
            'condition' =>                  array('type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isGenericName', 'values' => array('new', 'used', 'refurbished'), 'default' => 'new'),
            'show_price' =>                 array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'indexed' =>                    array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'visibility' =>                 array('type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isProductVisibility', 'values' => array('both', 'catalog', 'search', 'none'), 'default' => 'both'),
            'cache_default_attribute' =>    array('type' => self::TYPE_INT, 'shop' => true),
            'advanced_stock_management' =>  array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'date_add' =>                   array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDateFormat'),
            'date_upd' =>                   array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDateFormat'),

            /* Lang fields */
            'meta_description' =>           array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255),
            'meta_keywords' =>              array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255),
            'meta_title' =>                 array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128),
            'link_rewrite' =>   array(
                'type' => self::TYPE_STRING,
                'lang' => true,
                'validate' => 'isLinkRewrite',
                'required' => true,
                'size' => 128,
                'ws_modifier' => array(
                    'http_method' => WebserviceRequest::HTTP_POST,
                    'modifier' => 'modifierWsLinkRewrite'
                )
            ),
            'name' =>                       array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => true, 'size' => 128),
            'description' =>                array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'),
            'description_short' =>          array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'),
            'available_now' =>              array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255),
            'available_later' =>            array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'IsGenericName', 'size' => 255),

            'show_new' =>                   array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'show_bestsales' =>             array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'is_product_of_the_day' =>      array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'recipes' =>                    array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'),
        ),
        'associations' => array(
            'manufacturer' =>               array('type' => self::HAS_ONE),
            'supplier' =>                   array('type' => self::HAS_ONE),
            'default_category' =>           array('type' => self::HAS_ONE, 'field' => 'id_category_default', 'object' => 'Category'),
            'tax_rules_group' =>            array('type' => self::HAS_ONE),
            'categories' =>                 array('type' => self::HAS_MANY, 'field' => 'id_category', 'object' => 'Category', 'association' => 'category_product'),
            'stock_availables' =>           array('type' => self::HAS_MANY, 'field' => 'id_stock_available', 'object' => 'StockAvailable', 'association' => 'stock_availables'),
        ),
    );


    /*
    * module: advancedfeaturesvalues
    * date: 2015-06-03 10:41:04
    * version: 1.0.7
    */
    public function addFeaturesToDB($id_feature, $id_value, $cust = 0)
    {
        if ($cust)
        {
            $row = array('id_feature' => (int)$id_feature, 'custom' => 1);
            Db::getInstance()->insert('feature_value', $row);
            $id_value = Db::getInstance()->Insert_ID();
        }
        $row = array('id_feature' => (int)$id_feature, 'id_product' => (int)$this->id, 'id_feature_value' => (int)$id_value);
        Db::getInstance()->insert('feature_product', $row);
        SpecificPriceRule::applyAllRules(array((int)$this->id));
        if ($id_value)
            return ($id_value);
    }


    /*
    * module: advancedfeaturesvalues
    * date: 2015-06-03 10:41:04
    * version: 1.0.7
    */
    public static function getFrontFeaturesStatic($id_lang, $id_product)
    {
        if (!Feature::isFeatureActive())
            return array();
        if (!array_key_exists($id_product.'-'.$id_lang, self::$_frontFeaturesCache))
        {
            self::$_frontFeaturesCache[$id_product.'-'.$id_lang] = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                SELECT name, GROUP_CONCAT(value ORDER BY fv.position SEPARATOR ", ") AS value, pf.id_feature
                FROM '._DB_PREFIX_.'feature_product pf
                LEFT JOIN '._DB_PREFIX_.'feature_lang fl ON (fl.id_feature = pf.id_feature AND fl.id_lang = '.(int)$id_lang.')
                LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fvl.id_feature_value = pf.id_feature_value AND fvl.id_lang = '.(int)$id_lang.')
                LEFT JOIN '._DB_PREFIX_.'feature f ON f.id_feature = pf.id_feature
                LEFT JOIN '._DB_PREFIX_.'feature_value fv ON fv.id_feature_value = pf.id_feature_value
                '.Shop::addSqlAssociation('feature', 'f').'
                WHERE pf.id_product = '.(int)$id_product.'
                GROUP BY name, pf.id_feature
                ORDER BY f.position ASC'
            );
        }
        return self::$_frontFeaturesCache[$id_product.'-'.$id_lang];
    }


    /**
    * Get new products
    *
    * @param integer $id_lang Language id
    * @param integer $pageNumber Start from (optional)
    * @param integer $nbProducts Number of products to return (optional)
    * @return array New products
    */
    public static function getNewProducts($id_lang, $page_number = 0, $nb_products = 10, $count = false, $order_by = null, $order_way = null, Context $context = null)
    {
        if (!$context)
            $context = Context::getContext();

        $front = true;
        if (!in_array($context->controller->controller_type, array('front', 'modulefront')))
            $front = false;

        if ($page_number < 0) $page_number = 0;
        if ($nb_products < 1) $nb_products = 10;
        if (empty($order_by) || $order_by == 'position') $order_by = 'date_add';
        if (empty($order_way)) $order_way = 'DESC';
        if ($order_by == 'id_product' || $order_by == 'price' || $order_by == 'date_add' || $order_by == 'date_upd')
            $order_by_prefix = 'p';
        elseif ($order_by == 'name')
            $order_by_prefix = 'pl';
        if (!Validate::isOrderBy($order_by) || !Validate::isOrderWay($order_way))
            die(Tools::displayError());

        $sql_groups = '';
        if (Group::isFeatureActive())
        {
            $groups = FrontController::getCurrentCustomerGroups();
            $sql_groups = 'AND p.`id_product` IN (
                SELECT cp.`id_product`
                FROM `'._DB_PREFIX_.'category_group` cg
                LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = cg.`id_category`)
                WHERE cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').'
            )';
        }

        if (strpos($order_by, '.') > 0)
        {
            $order_by = explode('.', $order_by);
            $order_by_prefix = $order_by[0];
            $order_by = $order_by[1];
        }

        if ($count)
        {
            $sql = 'SELECT COUNT(p.`id_product`) AS nb
                    FROM `'._DB_PREFIX_.'product` p
                    '.Shop::addSqlAssociation('product', 'p').'
                    WHERE product_shop.`active` = 1
                    AND ((product_shop.`date_add` > "'.date('Y-m-d', strtotime('-'.(Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int)Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY')).'"
                    '.($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').'
                    '.$sql_groups.') OR product_shop.`show_new` = 1)';
            return (int)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        }

        $sql = new DbQuery();
        $sql->select(
            'p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`,
            pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`, MAX(image_shop.`id_image`) id_image, il.`legend`, m.`name` AS manufacturer_name,
            product_shop.`date_add` > "'.date('Y-m-d', strtotime('-'.(Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int)Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY')).'" as new'.(Combination::isFeatureActive() ? ', MAX(product_attribute_shop.minimal_quantity) AS product_attribute_minimal_quantity' : '')
        );

        $sql->from('product', 'p');
        $sql->join(Shop::addSqlAssociation('product', 'p'));
        $sql->leftJoin('product_lang', 'pl', '
            p.`id_product` = pl.`id_product`
            AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl')
        );
        $sql->leftJoin('image', 'i', 'i.`id_product` = p.`id_product`');
        $sql->join(Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1'));
        $sql->leftJoin('image_lang', 'il', 'i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)$id_lang);
        $sql->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`');

        $sql->where('product_shop.`active` = 1');
        if ($front)
            $sql->where('product_shop.`visibility` IN ("both", "catalog")');
        $sql->where('(product_shop.`date_add` > "'.date('Y-m-d', strtotime('-'.(Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int)Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY')).'" OR product_shop.`show_new` = 1)');
        if (Group::isFeatureActive())
        {
            $sql->join('JOIN '._DB_PREFIX_.'category_product cp ON (cp.id_product = p.id_product)');
            $sql->join('JOIN '._DB_PREFIX_.'category_group cg ON (cg.id_category = cp.id_category)');
            $sql->where('cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1'));
        }
        $sql->groupBy('product_shop.id_product');

        $sql->orderBy((isset($order_by_prefix) ? pSQL($order_by_prefix).'.' : '').'`'.pSQL($order_by).'` '.pSQL($order_way));
        $sql->limit($nb_products, $page_number * $nb_products);

        if (Combination::isFeatureActive())
        {
            $sql->select('MAX(product_attribute_shop.id_product_attribute) id_product_attribute');
            $sql->leftOuterJoin('product_attribute', 'pa', 'p.`id_product` = pa.`id_product`');
            $sql->join(Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.default_on = 1'));
        }
        $sql->join(Product::sqlStock('p', Combination::isFeatureActive() ? 'product_attribute_shop' : 0));

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if ($order_by == 'price')
            Tools::orderbyPrice($result, $order_way);
        if (!$result)
            return false;

        $products_ids = array();
        foreach ($result as $row)
            $products_ids[] = $row['id_product'];
        // Thus you can avoid one query per product, because there will be only one query for all the products of the cart
        Product::cacheFrontFeatures($products_ids, $id_lang);
        return Product::getProductsProperties((int)$id_lang, $result);
    }


    /**
    * Get prices drop
    *
    * @param integer $id_lang Language id
    * @param integer $pageNumber Start from (optional)
    * @param integer $nbProducts Number of products to return (optional)
    * @param boolean $count Only in order to get total number (optional)
    * @return array Prices drop
    */
    public static function getPricesDrop($id_lang, $page_number = 0, $nb_products = 10, $count = false,
        $order_by = null, $order_way = null, $beginning = false, $ending = false, Context $context = null)
    {
        if (!Validate::isBool($count))
            die(Tools::displayError());

        if (!$context) $context = Context::getContext();
        if ($page_number < 0) $page_number = 0;
        if ($nb_products < 1) $nb_products = 10;
        if (empty($order_by)) $order_by = 'RAND()';
        if (empty($order_by) || $order_by == 'position') $order_by = 'price';
        if (empty($order_way) && $order_by != 'RAND()') $order_way = 'DESC';
        if ($order_by == 'id_product' || $order_by == 'price' || $order_by == 'date_add' || $order_by == 'date_upd')
            $order_by_prefix = 'p';
        elseif ($order_by == 'name')
            $order_by_prefix = 'pl';
        // if (!Validate::isOrderBy($order_by) || !Validate::isOrderWay($order_way))
            // die (Tools::displayError());
        $current_date = date('Y-m-d H:i:s');
        $ids_product = Product::_getProductIdByDate((!$beginning ? $current_date : $beginning), (!$ending ? $current_date : $ending), $context);

        $tab_id_product = array();
        foreach ($ids_product as $product)
            if (is_array($product))
                $tab_id_product[] = (int)$product['id_product'];
            else
                $tab_id_product[] = (int)$product;

        $front = true;
        if (!in_array($context->controller->controller_type, array('front', 'modulefront')))
            $front = false;

        $sql_groups = '';
        if (Group::isFeatureActive())
        {
            $groups = FrontController::getCurrentCustomerGroups();
            $sql_groups = 'AND p.`id_product` IN (
                SELECT cp.`id_product`
                FROM `'._DB_PREFIX_.'category_group` cg
                LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = cg.`id_category`)
                WHERE cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').'
            )';
        }

        if ($count)
        {
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
            SELECT COUNT(DISTINCT p.`id_product`)
            FROM `'._DB_PREFIX_.'product` p
            '.Shop::addSqlAssociation('product', 'p').'
            WHERE product_shop.`active` = 1
            AND product_shop.`show_price` = 1
            '.($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').'
            '.((!$beginning && !$ending) ? 'AND p.`id_product` IN('.((is_array($tab_id_product) && count($tab_id_product)) ? implode(', ', $tab_id_product) : 0).')' : '').'
            '.$sql_groups);
        }

        if (strpos($order_by, '.') > 0)
        {
            $order_by = explode('.', $order_by);
            $order_by = pSQL($order_by[0]).'.`'.pSQL($order_by[1]).'`';
        }

        $sql = '
        SELECT
            p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`description`, pl.`description_short`,
            MAX(product_attribute_shop.id_product_attribute) id_product_attribute,
            pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`,
            pl.`name`, MAX(image_shop.`id_image`) id_image, il.`legend`, m.`name` AS manufacturer_name,
            DATEDIFF(
                p.`date_add`,
                DATE_SUB(
                    NOW(),
                    INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY
                )
            ) > 0 AS new
        FROM `'._DB_PREFIX_.'product` p
        '.Shop::addSqlAssociation('product', 'p').'
        LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON (pa.id_product = p.id_product)
        '.Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.default_on=1').'
        '.Product::sqlStock('p', 0, false, $context->shop).'
        LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
            p.`id_product` = pl.`id_product`
            AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').'
        )
        LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product`)'.
        Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
        LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)$id_lang.')
        LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
        WHERE product_shop.`active` = 1
        AND product_shop.`show_price` = 1
        '.($front ? ' AND p.`visibility` IN ("both", "catalog")' : '').'
        '.((!$beginning && !$ending) ? ' AND p.`id_product` IN ('.((is_array($tab_id_product) && count($tab_id_product)) ? implode(', ', $tab_id_product) : 0).')' : '').'
        '.$sql_groups.'
        GROUP BY product_shop.id_product
        ORDER BY '.(isset($order_by_prefix) ? pSQL($order_by_prefix).'.' : '').pSQL($order_by).' '.pSQL($order_way).'
        LIMIT '.(int)($page_number * $nb_products).', '.(int)$nb_products;

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (!$result)
            return false;

        if ($order_by == 'price')
            Tools::orderbyPrice($result, $order_way);

        return Product::getProductsProperties($id_lang, $result);
    }
}
