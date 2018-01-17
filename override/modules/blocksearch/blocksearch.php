<?php
if (!defined('_PS_VERSION_'))
    exit;

class BlockSearchOverride extends BlockSearch
{
    public function install()
    {
        if (!parent::install() || !$this->registerHook('displayOnCategoryPage'))
            return false;
        return true;
    }

    public function hookDisplayOnCategoryPage($params)
    {
        return $this->hookRightColumn($params);
    }
}
