<?php
/**
 * Created by PhpStorm.
 * User: softtt
 * Date: 12.09.2016
 * Time: 12:58
 */

class ViewedProductsController extends FrontController
{
    public $php_self = 'viewed-products';
    const VIEWED_PRODS_NUMBER = 20;
    const VIEWED_PER_PAGE = 10;
    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS(_THEME_CSS_DIR_.'product_list.css');
    }

    public function init() {
        parent::init();
    }
    public function initContent()
    {
        parent::initContent();

        $this->productSort();
        blockviewed::setViewedNumber($this::VIEWED_PRODS_NUMBER);
        $this->pagination($this::VIEWED_PER_PAGE);
        $productIds = explode(',',$this->context->cookie->viewed);

        $products = [];
        foreach($productIds as $id)
        {
            $prodsObj = blockviewed::getViewedProductForHome((int)$this->context->cookie->id_lang,$id);
            array_push($products,$prodsObj);
        }
        $this->addColorsToProductList($products);

        $this->context->smarty->assign(array(
            'products' => $products,
            'add_prod_display' => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
            'nbProducts' => $this::VIEWED_PRODS_NUMBER,
            'homeSize' => Image::getSize(ImageType::getFormatedName('home')),
            'comparator_max_item' => Configuration::get('PS_COMPARATOR_MAX_ITEM')
        ));

        $this->setTemplate(_PS_THEME_DIR_.'viewed-products.tpl');
    }
} 