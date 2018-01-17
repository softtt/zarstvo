<?php

if (!defined('_PS_VERSION_'))
    exit;

class ProductCommentsOverride extends ProductComments
{
    public function hookProductTabContent($params)
    {
        $this->context->controller->addJS($this->_path.'js/jquery.rating.pack.js');
        $this->context->controller->addJS($this->_path.'js/jquery.textareaCounter.plugin.js');
        $this->context->controller->addJS($this->_path.'js/productcomments.js');

        $id_guest = (!$id_customer = (int)$this->context->cookie->id_customer) ? (int)$this->context->cookie->id_guest : false;
        $customerComment = ProductComment::getByCustomer((int)(Tools::getValue('id_product')), (int)$this->context->cookie->id_customer, true, (int)$id_guest);

        $averages = ProductComment::getAveragesByProduct((int)Tools::getValue('id_product'), $this->context->language->id);
        $averageTotal = 0;
        foreach ($averages as $average)
            $averageTotal += (float)($average);
        $averageTotal = count($averages) ? ($averageTotal / count($averages)) : 0;

        $product = $this->context->controller->getProduct();
        $image = Product::getCover((int)Tools::getValue('id_product'));
        $cover_image = $this->context->link->getImageLink($product->link_rewrite, $image['id_image']);

        $this->context->smarty->assign(array(
            'logged' => $this->context->customer->isLogged(true),
            'action_url' => '',
            'product' => $product,
            'comments' => ProductComment::getByProduct((int)Tools::getValue('id_product'), 1, null, $this->context->cookie->id_customer),
            'criterions' => ProductCommentCriterion::getByProduct((int)Tools::getValue('id_product'), $this->context->language->id),
            'averages' => $averages,
            'product_comment_path' => $this->_path,
            'averageTotal' => $averageTotal,
            'allow_guests' => (int)Configuration::get('PRODUCT_COMMENTS_ALLOW_GUESTS'),
            'too_early' => ($customerComment && (strtotime($customerComment['date_add']) + Configuration::get('PRODUCT_COMMENTS_MINIMAL_TIME')) > time()),
            'delay' => Configuration::get('PRODUCT_COMMENTS_MINIMAL_TIME'),
            'id_product_comment_form' => (int)Tools::getValue('id_product'),
            'secure_key' => $this->secure_key,
            'productcomment_cover' => (int)Tools::getValue('id_product').'-'.(int)$image['id_image'],
            'productcomment_cover_image' => $cover_image,
            'mediumSize' => Image::getSize(ImageType::getFormatedName('medium')),
            'nbComments' => (int)ProductComment::getCommentNumber((int)Tools::getValue('id_product')),
            'productcomments_controller_url' => $this->context->link->getModuleLink('productcomments'),
            'productcomments_url_rewriting_activated' => Configuration::get('PS_REWRITING_SETTINGS', 0),
            'moderation_active' => (int)Configuration::get('PRODUCT_COMMENTS_MODERATE')
       ));

        $this->context->controller->pagination((int)ProductComment::getCommentNumber((int)Tools::getValue('id_product')));

        return ($this->display(__FILE__, '/productcomments.tpl'));
    }
}
