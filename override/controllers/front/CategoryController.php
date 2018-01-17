<?php
class CategoryController extends CategoryControllerCore
{
    public function canonicalRedirection($canonicalURL = '')
    {
        if (Tools::getValue('live_edit'))
            return;
        if (!Validate::isLoadedObject($this->category)
            || !$this->category->inShop()
            || !$this->category->isAssociatedToShop()
            // || in_array($this->category->id, array(Configuration::get('PS_HOME_CATEGORY'), Configuration::get('PS_ROOT_CATEGORY')))
        )
        {
            $this->redirect_after = '404';
            $this->redirect();
        }
        if (!Tools::getValue('noredirect') && Validate::isLoadedObject($this->category))
            FrontController::canonicalRedirection($this->context->link->getCategoryLink($this->category));
    }

    /**
     * Assign list of products template vars
     */
    public function assignProductList()
    {
        $hookExecuted = false;
        Hook::exec('actionProductListOverride', array(
            'nbProducts' => &$this->nbProducts,
            'catProducts' => &$this->cat_products,
            'hookExecuted' => &$hookExecuted,
        ));

        // The hook was not executed, standard working
        if (!$hookExecuted || is_null($this->nbProducts))
        {
            $this->context->smarty->assign('categoryNameComplement', '');
            $this->nbProducts = $this->category->getProducts(null, null, null, $this->orderBy, $this->orderWay, true);
            $this->pagination((int)$this->nbProducts); // Pagination must be call after "getProducts"
            $this->cat_products = $this->category->getProducts($this->context->language->id, (int)$this->p, (int)$this->n, $this->orderBy, $this->orderWay);
        }
        // Hook executed, use the override
        else
            // Pagination must be call after "getProducts"
            $this->pagination($this->nbProducts);

        Hook::exec('actionProductListModifier', array(
            'nb_products' => &$this->nbProducts,
            'cat_products' => &$this->cat_products,
        ));

        foreach ($this->cat_products as &$product)
            if ($product['id_product_attribute'] && isset($product['product_attribute_minimal_quantity']))
                $product['minimal_quantity'] = $product['product_attribute_minimal_quantity'];

        $this->addColorsToProductList($this->cat_products);

        $this->context->smarty->assign('nb_products', $this->nbProducts);
    }

}
