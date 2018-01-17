<?php

if (!defined('_PS_VERSION_'))
    exit;

class BlockUserInfoOverride extends BlockUserInfo
{
    public function hookDisplayNav($params)
    {
        $this->smarty->assign(array(
            'discount' => $this->context->customer->getDiscountCard(),
        ));
        return $this->display(__FILE__, 'nav.tpl');
    }
}
