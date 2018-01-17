<?php

class MyAccountController extends MyAccountControllerCore
{
    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        FrontController::initContent();

        $has_address = $this->context->customer->getAddresses($this->context->language->id);

        $this->context->smarty->assign(array(
            'has_customer_an_address' => empty($has_address),
            'voucherAllowed' => (int)CartRule::isFeatureActive(),
            'returnAllowed' => (int)Configuration::get('PS_ORDER_RETURN'),
        ));

        if ($discount = $this->context->customer->getDiscountCard())
            $this->context->smarty->assign(array(
                'discount_code' => $discount->getCardCode(),
                'discount_percent' => $discount->getDiscountPercent(),
            ));

        $this->context->smarty->assign('HOOK_CUSTOMER_ACCOUNT', Hook::exec('displayCustomerAccount'));

        $this->setTemplate(_PS_THEME_DIR_.'my-account.tpl');
    }
}
