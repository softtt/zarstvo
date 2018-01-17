<?php

if (!defined('_PS_VERSION_'))
    exit;

class CrossSellingOverride extends CrossSelling
{

    /**
     * Returns module content
     */
    public function hookshoppingCart($params)
    {
        if (!$params['products'])
            return;

        $order_products = [];
        $cache_id = 'crossselling|shoppingcart|'.(int)$params['products'];

        if (!$this->isCached('crossselling.tpl', $this->getCacheId($cache_id)))
        {
            $q_orders = 'SELECT o.id_order
            FROM '._DB_PREFIX_.'orders o
            LEFT JOIN '._DB_PREFIX_.'order_detail od ON (od.id_order = o.id_order)
            WHERE o.valid = 1 AND (';
            $nb_products = count($params['products']);
            $i = 1;
            $products_id = array();
            foreach ($params['products'] as $product)
            {
                $q_orders .= 'od.product_id = '.(int)$product['id_product'];
                if ($i < $nb_products)
                    $q_orders .= ' OR ';
                ++$i;
                $products_id[] = (int)$product['id_product'];
            }
            $q_orders .= ')';
            $orders = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($q_orders);
            $tax_calc = Product::getTaxCalculationMethod();

            $order_filter = '';
            if (count($orders))
            {
                $list = '';
                foreach ($orders as $order)
                    $list .= (int)$order['id_order'].',';
                $list = rtrim($list, ',');
                $order_filter = 'od.id_order IN ('.$list.') AND';
            }

            $list_product_ids = join(',', $products_id);

            if (Group::isFeatureActive())
            {
                $sql_groups_join = '
                LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = product_shop.id_category_default AND cp.id_product = product_shop.id_product)
                LEFT JOIN `'._DB_PREFIX_.'category_group` cg ON (cp.`id_category` = cg.`id_category`)';
                $groups = FrontController::getCurrentCustomerGroups();
                $sql_groups_where = 'AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '='.(int)Group::getCurrent()->id);
            }

            $order_products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                'SELECT DISTINCT od.product_id, pl.name, pl.link_rewrite, p.reference, i.id_image, product_shop.show_price, cl.link_rewrite category, p.ean13, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity
                FROM '._DB_PREFIX_.'order_detail od
                LEFT JOIN '._DB_PREFIX_.'product p ON (p.id_product = od.product_id)
                '.Shop::addSqlAssociation('product', 'p').
                (Combination::isFeatureActive() ? 'LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
                ON (p.`id_product` = pa.`id_product`)
                '.Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.`default_on` = 1').'
                '.Product::sqlStock('p', 'product_attribute_shop', false, $this->context->shop) :  Product::sqlStock('p', 'product', false, $this->context->shop)).'
                LEFT JOIN '._DB_PREFIX_.'product_lang pl ON (pl.id_product = od.product_id'.Shop::addSqlRestrictionOnLang('pl').')
                LEFT JOIN '._DB_PREFIX_.'category_lang cl ON (cl.id_category = product_shop.id_category_default'.Shop::addSqlRestrictionOnLang('cl').')
                LEFT JOIN '._DB_PREFIX_.'image i ON (i.id_product = od.product_id)
                '.$sql_groups_join.'
                WHERE
                    '.$order_filter.'
                    pl.id_lang = '.(int)$this->context->language->id.'
                    AND cl.id_lang = '.(int)$this->context->language->id.'
                    AND od.product_id NOT IN ('.$list_product_ids.')
                    AND i.cover = 1
                    AND product_shop.active = 1
                    '.$sql_groups_where.'
                LIMIT '.(int)Configuration::get('CROSSSELLING_NBR')
                .'
                UNION SELECT DISTINCT
                    p.id_product as product_id,
                    pl.name,
                    pl.link_rewrite,
                    p.reference,
                    i.id_image,
                    product_shop.show_price,
                    cl.link_rewrite category,
                    p.ean13,
                    stock.out_of_stock,
                    IFNULL(stock.quantity, 0) as quantity
                FROM '._DB_PREFIX_.'product p
                '.Shop::addSqlAssociation('product', 'p').
                (Combination::isFeatureActive() ? 'LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
                ON (p.`id_product` = pa.`id_product`)
                '.Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.`default_on` = 1').'
                '.Product::sqlStock('p', 'product_attribute_shop', false, $this->context->shop) :  Product::sqlStock('p', 'product', false, $this->context->shop)).'
                LEFT JOIN '._DB_PREFIX_.'product_lang pl
                    ON (pl.id_product = p.id_product AND pl.id_shop = 1 )
                LEFT JOIN '._DB_PREFIX_.'category_lang cl
                    ON (cl.id_category = product_shop.id_category_default AND cl.id_shop = 1 )
                LEFT JOIN '._DB_PREFIX_.'image i
                    ON (i.id_product = p.id_product)
                '.$sql_groups_join.'
                WHERE
                    pl.id_lang = '.(int)$this->context->language->id.'
                    AND cl.id_lang = '.(int)$this->context->language->id.'
                    AND p.id_product NOT IN ('.$list_product_ids.')
                    AND i.cover = 1
                    AND product_shop.active = 1
                    AND cg.`id_group` = 1
                    '.$sql_groups_where.'
                ORDER BY RAND()
                LIMIT 10'
            );

            foreach ($order_products as &$order_product)
            {
                $order_product['id_product'] = (int)$order_product['product_id'];
                $order_product['id_product_attribute'] = (int)0;
                $order_product['add_prod_display'] = (int)1;
                $order_product['available_for_order'] = (int)1;
                $order_product['image'] = $this->context->link->getImageLink($order_product['link_rewrite'], (int)$order_product['product_id'].'-'.(int)$order_product['id_image'], ImageType::getFormatedName('home'));
                $order_product['link'] = $this->context->link->getProductLink((int)$order_product['product_id'], $order_product['link_rewrite'], $order_product['category'], $order_product['ean13']);
                if (Configuration::get('CROSSSELLING_DISPLAY_PRICE') && ($tax_calc == 0 || $tax_calc == 2))
                    $order_product['price'] = Product::getPriceStatic((int)$order_product['product_id'], true, null);
                elseif (Configuration::get('CROSSSELLING_DISPLAY_PRICE') && $tax_calc == 1)
                    $order_product['price'] = Product::getPriceStatic((int)$order_product['product_id'], false, null);
                $order_product['allow_oosp'] = Product::isAvailableWhenOutOfStock((int)$order_product['out_of_stock']);
            }

            $this->smarty->assign(
                array(
                    'order' => (count($products_id) > 1 ? true : false),
                    'orderProducts' => $order_products,
                    'middlePosition_crossselling' => round(count($order_products) / 2, 0),
                    'crossDisplayPrice' => Configuration::get('CROSSSELLING_DISPLAY_PRICE'),
                    'add_prod_display' => 1
                )
            );
        }

        return $this->display(__FILE__, 'crossselling.tpl', $this->getCacheId($cache_id));
    }


    /**
     * Returns module content for left column
     */
    public function hookProductFooter($params)
    {
        $cache_id = 'crossselling|productfooter|'.(int)$params['product']->id;
        $order_products = [];

        if (!$this->isCached('crossselling.tpl', $this->getCacheId($cache_id)))
        {
            $orders = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT o.id_order
            FROM '._DB_PREFIX_.'orders o
            LEFT JOIN '._DB_PREFIX_.'order_detail od ON (od.id_order = o.id_order)
            WHERE o.valid = 1 AND od.product_id = '.(int)$params['product']->id
            );

            $tax_calc = Product::getTaxCalculationMethod();
            if (count($orders))
            {
                $list = '';
                foreach ($orders as $order)
                    $list .= (int)$order['id_order'].',';
                $list = rtrim($list, ',');

                if (Group::isFeatureActive())
                {
                    $sql_groups_join = '
                    LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = product_shop.id_category_default AND cp.id_product = product_shop.id_product)
                    LEFT JOIN `'._DB_PREFIX_.'category_group` cg ON (cp.`id_category` = cg.`id_category`)';
                    $groups = FrontController::getCurrentCustomerGroups();
                    $sql_groups_where = 'AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '='.(int)Group::getCurrent()->id);
                }

                $order_products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    'SELECT DISTINCT od.product_id, pl.name, pl.link_rewrite, p.reference, i.id_image, product_shop.show_price, cl.link_rewrite category, p.ean13, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity
                    FROM '._DB_PREFIX_.'order_detail od
                    LEFT JOIN '._DB_PREFIX_.'product p ON (p.id_product = od.product_id)
                    '.Shop::addSqlAssociation('product', 'p').
                    (Combination::isFeatureActive() ? 'LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
                    ON (p.`id_product` = pa.`id_product`)
                    '.Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.`default_on` = 1').'
                    '.Product::sqlStock('p', 'product_attribute_shop', false, $this->context->shop) :  Product::sqlStock('p', 'product', false, $this->context->shop)).'
                    LEFT JOIN '._DB_PREFIX_.'product_lang pl ON (pl.id_product = od.product_id'.Shop::addSqlRestrictionOnLang('pl').')
                    LEFT JOIN '._DB_PREFIX_.'category_lang cl ON (cl.id_category = product_shop.id_category_default'.Shop::addSqlRestrictionOnLang('cl').')
                    LEFT JOIN '._DB_PREFIX_.'image i ON (i.id_product = od.product_id)
                    '.$sql_groups_join.'
                    WHERE od.id_order IN ('.$list.')
                    AND pl.id_lang = '.(int)$this->context->language->id.'
                    AND cl.id_lang = '.(int)$this->context->language->id.'
                    AND od.product_id != '.(int)$params['product']->id.'
                    AND i.cover = 1
                    AND product_shop.active = 1
                    '.$sql_groups_where.'
                    ORDER BY RAND()
                    LIMIT '.(int)Configuration::get('CROSSSELLING_NBR')
                );

                foreach ($order_products as &$order_product)
                {
                    $order_product['id_product'] = (int)$order_product['product_id'];
                    $order_product['id_product_attribute'] = (int)0;
                    $order_product['add_prod_display'] = (int)1;
                    $order_product['available_for_order'] = (int)1;
                    $order_product['image'] = $this->context->link->getImageLink($order_product['link_rewrite'], (int)$order_product['product_id'].'-'.(int)$order_product['id_image'], ImageType::getFormatedName('home'));
                    $order_product['link'] = $this->context->link->getProductLink((int)$order_product['product_id'], $order_product['link_rewrite'], $order_product['category'], $order_product['ean13']);
                    if (Configuration::get('CROSSSELLING_DISPLAY_PRICE') && ($tax_calc == 0 || $tax_calc == 2))
                        $order_product['price'] = Product::getPriceStatic((int)$order_product['product_id'], true, null);
                    elseif (Configuration::get('CROSSSELLING_DISPLAY_PRICE') && $tax_calc == 1)
                        $order_product['price'] = Product::getPriceStatic((int)$order_product['product_id'], false, null);
                    $order_product['allow_oosp'] = Product::isAvailableWhenOutOfStock((int)$order_product['out_of_stock']);
                }
            }

            $category = new Category((int)$params['product']->id_category_default);
            $load_products = 10 - count($order_products);
            $category_products = $category->getProducts((int)$this->context->language->id, 1, $load_products, null, null, false, true, true, $load_products);
            foreach ($category_products as &$order_product) {
                if ((int)$order_product['id_product'] !== (int)$params['product']->id) {
                    $order_product['product_id'] = (int)$order_product['id_product'];
                    $order_product['id_product'] = (int)$order_product['product_id'];
                    $order_product['id_product_attribute'] = (int)0;
                    $order_product['add_prod_display'] = (int)1;
                    $order_product['available_for_order'] = (int)1;
                    $order_product['image'] = $this->context->link->getImageLink($order_product['link_rewrite'], (int)$order_product['product_id'].'-'.(int)$order_product['id_image'], ImageType::getFormatedName('home'));
                    $order_product['link'] = $this->context->link->getProductLink((int)$order_product['product_id'], $order_product['link_rewrite'], $order_product['category'], $order_product['ean13']);
                    if (Configuration::get('CROSSSELLING_DISPLAY_PRICE') && ($tax_calc == 0 || $tax_calc == 2))
                        $order_product['price'] = Product::getPriceStatic((int)$order_product['product_id'], true, null);
                    elseif (Configuration::get('CROSSSELLING_DISPLAY_PRICE') && $tax_calc == 1)
                        $order_product['price'] = Product::getPriceStatic((int)$order_product['product_id'], false, null);
                    $order_product['allow_oosp'] = Product::isAvailableWhenOutOfStock((int)$order_product['out_of_stock']);
                    $order_products[] = $order_product;
                }
            }
            $this->smarty->assign(
                array(
                    'order' => false,
                    'orderProducts' => $order_products,
                    'middlePosition_crossselling' => round(count($order_products) / 2, 0),
                    'crossDisplayPrice' => Configuration::get('CROSSSELLING_DISPLAY_PRICE'),
                    'add_prod_display' => 1
                )
            );
        }

        return $this->display(__FILE__, 'crossselling.tpl', $this->getCacheId($cache_id));
    }
}
