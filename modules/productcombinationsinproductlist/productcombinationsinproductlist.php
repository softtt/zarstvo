<?php
/**
 * @author Victor Scherba <dev@smart-raccoon.com>
 * @link   http://smart-raccoon.com
 */

if (!defined('_PS_VERSION_'))
    exit;

class ProductCombinationsInProductList extends Module
{
    public function __construct()
    {
        $this->name = 'productcombinationsinproductlist';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'Smart Raccoon';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Product combination in product list');
        $this->description = $this->l('Show product combination select in product lists templates.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('PRODUCTCOMBINATIONSONPRODUCTLIST_NAME'))
            $this->warning = $this->l('No name provided');
    }


    public function install()
    {
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);

        if (!parent::install() ||
            !$this->registerHook('displayProductModifications') ||
            !$this->registerHook('header') ||
            !Configuration::updateValue('PRODUCTCOMBINATIONSONPRODUCTLIST_NAME', 'Product combination in product list')
        )
            return false;

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() ||
            !Configuration::deleteByName('PRODUCTCOMBINATIONSONPRODUCTLIST_NAME')
        )
            return false;

        return true;
    }

    public function hookHeader($params)
    {
        $this->context->controller->addJS(($this->_path).'js/productcombinationsinproductlist.js');
    }

    public function hookDisplayProductModifications($params)
    {
        return $this->assignAttributesGroups($params['product']['id_product']);
    }

    /**
     * Assign template vars related to attribute groups and colors
     */
    protected function assignAttributesGroups($product_id)
    {
        $colors = array();
        $groups = array();
        $combinations = array();

        $product = new Product($product_id, true);

        // @todo (RM) should only get groups and not all declination ?
        $attributes_groups = $product->getAttributesGroups($this->context->language->id);
        if (is_array($attributes_groups) && $attributes_groups)
        {
            $combination_images = $product->getCombinationImages($this->context->language->id);
            $combination_prices_set = array();
            foreach ($attributes_groups as $k => $row)
            {
                // Color management
                if (isset($row['is_color_group']) && $row['is_color_group'] && (isset($row['attribute_color']) && $row['attribute_color']) || (file_exists(_PS_COL_IMG_DIR_.$row['id_attribute'].'.jpg')))
                {
                    $colors[$row['id_attribute']]['value'] = $row['attribute_color'];
                    $colors[$row['id_attribute']]['name'] = $row['attribute_name'];
                    if (!isset($colors[$row['id_attribute']]['attributes_quantity']))
                        $colors[$row['id_attribute']]['attributes_quantity'] = 0;
                    $colors[$row['id_attribute']]['attributes_quantity'] += (int)$row['quantity'];
                }
                if (!isset($groups[$row['id_attribute_group']]))
                    $groups[$row['id_attribute_group']] = array(
                        'group_name' => $row['group_name'],
                        'name' => $row['public_group_name'],
                        'group_type' => $row['group_type'],
                        'default' => -1,
                    );

                $groups[$row['id_attribute_group']]['attributes'][$row['id_attribute']] = $row['attribute_name'];
                if ($row['default_on'] && $groups[$row['id_attribute_group']]['default'] == -1)
                    $groups[$row['id_attribute_group']]['default'] = (int)$row['id_attribute'];
                if (!isset($groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']]))
                    $groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']] = 0;
                $groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']] += (int)$row['quantity'];

                $combinations[$row['id_product_attribute']]['attributes_values'][$row['id_attribute_group']] = $row['attribute_name'];
                $combinations[$row['id_product_attribute']]['attributes'][] = (int)$row['id_attribute'];
                $combinations[$row['id_product_attribute']]['price'] = (float)$row['price'];

                // Call getPriceStatic in order to set $combination_specific_price
                if (!isset($combination_prices_set[(int)$row['id_product_attribute']]))
                {
                    Product::getPriceStatic((int)$product->id, false, $row['id_product_attribute'], 6, null, false, true, 1, false, null, null, null, $combination_specific_price);
                    $combination_prices_set[(int)$row['id_product_attribute']] = true;
                    $combinations[$row['id_product_attribute']]['specific_price'] = $combination_specific_price;
                }
                $combinations[$row['id_product_attribute']]['ecotax'] = (float)$row['ecotax'];
                $combinations[$row['id_product_attribute']]['weight'] = (float)$row['weight'];
                $combinations[$row['id_product_attribute']]['quantity'] = (int)$row['quantity'];
                $combinations[$row['id_product_attribute']]['reference'] = $row['reference'];
                $combinations[$row['id_product_attribute']]['unit_impact'] = $row['unit_price_impact'];
                $combinations[$row['id_product_attribute']]['minimal_quantity'] = $row['minimal_quantity'];
                if ($row['available_date'] != '0000-00-00')
                {
                    $combinations[$row['id_product_attribute']]['available_date'] = $row['available_date'];
                    $combinations[$row['id_product_attribute']]['date_formatted'] = Tools::displayDate($row['available_date']);
                }
                else
                    $combinations[$row['id_product_attribute']]['available_date'] = '';
            }

            // wash attributes list (if some attributes are unavailables and if allowed to wash it)
            if (!Product::isAvailableWhenOutOfStock($product->out_of_stock) && Configuration::get('PS_DISP_UNAVAILABLE_ATTR') == 0)
            {
                foreach ($groups as &$group)
                    foreach ($group['attributes_quantity'] as $key => &$quantity)
                        if ($quantity <= 0)
                            unset($group['attributes'][$key]);

                foreach ($colors as $key => $color)
                    if ($color['attributes_quantity'] <= 0)
                        unset($colors[$key]);
            }
            foreach ($combinations as $id_product_attribute => $comb)
            {
                $attribute_list = '';
                foreach ($comb['attributes'] as $id_attribute)
                    $attribute_list .= '\''.(int)$id_attribute.'\',';
                $attribute_list = rtrim($attribute_list, ',');
                $combinations[$id_product_attribute]['list'] = $attribute_list;
            }

        }

        $id_group = (int)Group::getCurrent()->id;
        $group_reduction = GroupReduction::getValueForProduct($product->id, $id_group);
        if ($group_reduction === false)
            $group_reduction = Group::getReduction((int)$this->context->cookie->id_customer) / 100;


        $this->context->smarty->assign(array(
            'mod_groups' => $groups,
            'mod_colors' => (count($colors)) ? $colors : false,
            'mod_combinations' => $combinations,
            'mod_product' => $product,
            'mod_allow_oosp' => Product::isAvailableWhenOutOfStock($product->out_of_stock),
            'mod_group_reduction' => $group_reduction,
        ));

        return $this->display(__FILE__, 'combinations.tpl');
    }
}
