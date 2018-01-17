<?php

class ProductSale extends ProductSaleCore
{
    /*
    ** Get number of actives products sold
    ** @return int number of actives products listed in product_sales
    */
    public static function getNbSales()
    {
        $sql = 'SELECT COUNT(p.`id_product`) AS nb
                FROM `'._DB_PREFIX_.'product` p
                LEFT JOIN `'._DB_PREFIX_.'product_sale` ps ON ps.`id_product` = p.`id_product`
                '.Shop::addSqlAssociation('product', 'p', false).'
                WHERE product_shop.`active` = 1
                AND (ps.`quantity` > 0 OR product_shop.`show_bestsales` = 1)';
        return (int)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }


    /*
    ** Get required informations on best sales products
    **
    ** @param integer $id_lang Language id
    ** @param integer $page_number Start from (optional)
    ** @param integer $nb_products Number of products to return (optional)
    ** @return array from Product::getProductProperties
    */
    public static function getBestSales($id_lang, $page_number = 0, $nb_products = 10, $order_by = null, $order_way = null)
    {
        if ($page_number < 0) $page_number = 0;
        if ($nb_products < 1) $nb_products = 10;
        $final_order_by = $order_by;
        $order_table = '';
        if (is_null($order_by) || $order_by == 'position' || $order_by == 'price') $order_by = 'sales';
        if ($order_by == 'date_add' || $order_by == 'date_upd')
            $order_table = 'product_shop';
        if (is_null($order_way) || $order_by == 'sales') $order_way = 'DESC';

        $interval = Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20;
        $sql = 'SELECT product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity,
                    IF (IFNULL(stock.quantity, 0) > 0, 1, 0) AS is_in_stock,
                    pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`,
                    pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`,
                    m.`name` AS manufacturer_name, p.`id_manufacturer` as id_manufacturer,
                    MAX(image_shop.`id_image`) id_image, il.`legend`,
                    ps.`quantity` AS sales, t.`rate`, pl.`meta_keywords`, pl.`meta_title`, pl.`meta_description`,
                    DATEDIFF(p.`date_add`, DATE_SUB(NOW(),
                    INTERVAL '.(int)$interval.' DAY)) > 0 AS new'.(Combination::isFeatureActive() ? ', MAX(product_attribute_shop.minimal_quantity) AS product_attribute_minimal_quantity' : '')
                .' FROM `'._DB_PREFIX_.'product` p
                LEFT JOIN `'._DB_PREFIX_.'product_sale` ps ON p.`id_product` = ps.`id_product`
                '.Shop::addSqlAssociation('product', 'p', false).'
                LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
                ON (p.`id_product` = pa.`id_product`)
                '.(Combination::isFeatureActive() ?
                Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.`default_on` = 1').'
                '.Product::sqlStock('p', 'product_attribute_shop', false, Context::getContext()->shop) : Product::sqlStock('p', 'product', false, Context::getContext()->shop)).'
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
                    ON p.`id_product` = pl.`id_product`
                    AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').'
                LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product`)'.
                Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
                LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)$id_lang.')
                LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
                LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON (product_shop.`id_tax_rules_group` = tr.`id_tax_rules_group`)
                    AND tr.`id_country` = '.(int)Context::getContext()->country->id.'
                    AND tr.`id_state` = 0
                LEFT JOIN `'._DB_PREFIX_.'tax` t ON (t.`id_tax` = tr.`id_tax`)';

            if (Group::isFeatureActive())
            {
                $groups = FrontController::getCurrentCustomerGroups();
                $sql .= '
                    JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_product` = p.`id_product`)
                    JOIN `'._DB_PREFIX_.'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').')';
            }

            $sql .= '
                WHERE product_shop.`active` = 1
                    AND p.`visibility` != \'none\'
                    AND (ps.`quantity` > 0 OR product_shop.`show_bestsales` = 1)
                GROUP BY product_shop.id_product
                ORDER BY is_in_stock DESC, '.(!empty($order_table) ? '`'.pSQL($order_table).'`.' : '').'`'.pSQL($order_by).'` '.pSQL($order_way).'
                LIMIT '.(int)($page_number * $nb_products).', '.(int)$nb_products;
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if ($final_order_by == 'price')
            Tools::orderbyPrice($result, $order_way);
        if (!$result)
            return false;
        return Product::getProductsProperties($id_lang, $result);
    }

    /*
    ** Get required informations on best sales products
    **
    ** @param integer $id_lang Language id
    ** @param integer $page_number Start from (optional)
    ** @param integer $nb_products Number of products to return (optional)
    ** @return array keys : id_product, link_rewrite, name, id_image, legend, sales, ean13, upc, link
    */
    public static function getBestSalesLight($id_lang, $page_number = 0, $nb_products = 10, Context $context = null)
    {
        if (!$context)
            $context = Context::getContext();
        if ($page_number < 0) $page_number = 0;
        if ($nb_products < 1) $nb_products = 10;

        $sql = '
        SELECT
            p.id_product, product_shop.`show_bestsales`, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, pl.`link_rewrite`, pl.`name`, pl.`description_short`, product_shop.`id_category_default`,
            MAX(image_shop.`id_image`) id_image, il.`legend`,
            IFNULL(ps.`quantity`, 0) AS sales, p.`ean13`, p.`upc`, cl.`link_rewrite` AS category, p.show_price, p.available_for_order, IFNULL(stock.quantity, 0) as quantity, p.customizable,
            IFNULL(pa.minimal_quantity, p.minimal_quantity) as minimal_quantity, stock.out_of_stock,
            product_shop.`date_add` > "'.date('Y-m-d', strtotime('-'.(Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int)Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY')).'" as new,
            product_shop.`on_sale`, MAX(product_attribute_shop.minimal_quantity) AS product_attribute_minimal_quantity
        FROM `'._DB_PREFIX_.'product` p
        LEFT JOIN `'._DB_PREFIX_.'product_sale` ps ON p.`id_product` = ps.`id_product`
        '.Shop::addSqlAssociation('product', 'p').'
        LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
            ON (p.`id_product` = pa.`id_product`)
        '.Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.`default_on` = 1').'
        '.Product::sqlStock('p', 'product_attribute_shop', false, $context->shop).'
        LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
            ON p.`id_product` = pl.`id_product`
            AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').'
        LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product`)'.
        Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
        LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)$id_lang.')
        LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
            ON cl.`id_category` = product_shop.`id_category_default`
            AND cl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl');

        if (Group::isFeatureActive())
        {
            $groups = FrontController::getCurrentCustomerGroups();
            $sql .= '
                JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_product` = p.`id_product`)
                JOIN `'._DB_PREFIX_.'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').')';
        }

        $sql.= '
        WHERE product_shop.`active` = 1
        AND p.`visibility` != \'none\'
        AND (ps.`quantity` > 0 OR product_shop.`show_bestsales` = 1)
        AND stock.quantity > 0
        GROUP BY product_shop.id_product
        ORDER BY RAND()
        LIMIT '.(int)($page_number * $nb_products).', '.(int)$nb_products;

        if (!$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql))
            return false;

        return Product::getProductsProperties($id_lang, $result);
    }
}
