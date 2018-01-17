<?php

class AdminCartRulesController extends AdminCartRulesControllerCore
{
    public function renderForm()
    {
        $back = Tools::safeOutput(Tools::getValue('back', ''));
        if (empty($back))
            $back = self::$currentIndex.'&token='.$this->token;

        $this->toolbar_btn['save-and-stay'] = array(
            'href' => '#',
            'desc' => $this->l('Save and Stay')
        );

        $current_object = $this->loadObject(true);

        // All the filter are prefilled with the correct information
        $customer_filter = '';
        if (Validate::isUnsignedId($current_object->id_customer) &&
            ($customer = new Customer($current_object->id_customer)) &&
            Validate::isLoadedObject($customer))
            $customer_filter = $customer->firstname.' '.$customer->lastname.' ('.$customer->email.')';

        $gift_product_filter = '';
        if (Validate::isUnsignedId($current_object->gift_product) &&
            ($product = new Product($current_object->gift_product, false, $this->context->language->id)) &&
            Validate::isLoadedObject($product))
            $gift_product_filter = (!empty($product->reference) ? $product->reference : $product->name);

        $reduction_product_filter = '';
        if (Validate::isUnsignedId($current_object->reduction_product) &&
            ($product = new Product($current_object->reduction_product, false, $this->context->language->id)) &&
            Validate::isLoadedObject($product))
            $reduction_product_filter = (!empty($product->reference) ? $product->reference : $product->name);

        $product_rule_groups = $this->getProductRuleGroupsDisplay($current_object);

        $attribute_groups = AttributeGroup::getAttributesGroups($this->context->language->id);
        $currencies = Currency::getCurrencies(false, true, true);
        $languages = Language::getLanguages();
        $countries = $current_object->getAssociatedRestrictions('country', true, true);
        $groups = $current_object->getAssociatedRestrictions('group', false, true);
        $shops = $current_object->getAssociatedRestrictions('shop', false, false);
        $cart_rules = $current_object->getAssociatedRestrictions('cart_rule', false, true);
        $carriers = $current_object->getAssociatedRestrictions('carrier', true, false);
        foreach ($carriers as &$carriers2)
            foreach ($carriers2 as &$carrier)
                foreach ($carrier as $field => &$value)
                    if ($field == 'name' && $value == '0')
                        $value = Configuration::get('PS_SHOP_NAME');

        $gift_product_select = '';
        $gift_product_attribute_select = '';
        if ((int)$current_object->gift_product)
        {
            $search_products = $this->searchProducts($gift_product_filter);
            if (isset($search_products['products']) && is_array($search_products['products']))
                foreach ($search_products['products'] as $product)
                {
                    $gift_product_select .= '
                    <option value="'.$product['id_product'].'" '.($product['id_product'] == $current_object->gift_product ? 'selected="selected"' : '').'>
                        '.$product['name'].(count($product['combinations']) == 0 ? ' - '.$product['formatted_price'] : '').'
                    </option>';

                    if (count($product['combinations']))
                    {
                        $gift_product_attribute_select .= '<select class="control-form id_product_attribute" id="ipa_'.$product['id_product'].'" name="ipa_'.$product['id_product'].'">';
                        foreach ($product['combinations'] as $combination)
                        {
                            $gift_product_attribute_select .= '
                            <option '.($combination['id_product_attribute'] == $current_object->gift_product_attribute ? 'selected="selected"' : '').' value="'.$combination['id_product_attribute'].'">
                                '.$combination['attributes'].' - '.$combination['formatted_price'].'
                            </option>';
                        }
                        $gift_product_attribute_select .= '</select>';
                    }
                }
        }

        $product = new Product($current_object->gift_product);
        $this->context->smarty->assign(
            array(
                'show_toolbar' => true,
                'toolbar_btn' => $this->toolbar_btn,
                'toolbar_scroll' => $this->toolbar_scroll,
                'title' => array($this->l('Payment: '), $this->l('Cart Rules')),
                'defaultDateFrom' => date('Y-m-d H:00:00'),
                'defaultDateTo' => date('Y-m-d H:00:00', strtotime('+1 month')),
                'customerFilter' => $customer_filter,
                'giftProductFilter' => $gift_product_filter,
                'gift_product_select' => $gift_product_select,
                'gift_product_attribute_select' => $gift_product_attribute_select,
                'reductionProductFilter' => $reduction_product_filter,
                'defaultCurrency' => Configuration::get('PS_CURRENCY_DEFAULT'),
                'id_lang_default' => Configuration::get('PS_LANG_DEFAULT'),
                'languages' => $languages,
                'currencies' => $currencies,
                'countries' => $countries,
                'carriers' => $carriers,
                'groups' => $groups,
                'shops' => $shops,
                'cart_rules' => $cart_rules,
                'product_rule_groups' => $product_rule_groups,
                'product_rule_groups_counter' => count($product_rule_groups),
                'attribute_groups' => $attribute_groups,
                'currentIndex' => self::$currentIndex,
                'currentToken' => $this->token,
                'currentObject' => $current_object,
                'currentTab' => $this,
                'hasAttribute' => $product->hasAttributes(),
            )
        );

        $this->content .= $this->createTemplate('form.tpl')->fetch();

        $this->addJqueryUI('ui.datepicker');
        return AdminController::renderForm();
    }
}
