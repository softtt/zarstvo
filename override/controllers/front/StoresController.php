<?php

class StoresController extends StoresControllerCore
{
    /**
     * Assign template vars for simplified stores
     */
    protected function assignStoresSimplified()
    {
        $stores = Db::getInstance()->executeS('
        SELECT s.*, cl.name country, st.iso_code state
        FROM '._DB_PREFIX_.'store s
        '.Shop::addSqlAssociation('store', 's').'
        LEFT JOIN '._DB_PREFIX_.'country_lang cl ON (cl.id_country = s.id_country)
        LEFT JOIN '._DB_PREFIX_.'state st ON (st.id_state = s.id_state)
        WHERE s.active = 1 AND cl.id_lang = '.(int)$this->context->language->id.'
        ORDER BY `city` ASC');

        $this->context->smarty->assign(array(
            'simplifiedStoresDiplay' => true,
            'stores' => $stores,
            'addresses_formated' => $addresses_formated,
        ));
    }

    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        FrontController::initContent();

        $this->assignStores();
        $this->assignStoresSimplified();

        $this->context->smarty->assign(array(
            'mediumSize' => Image::getSize(ImageType::getFormatedName('medium')),
            'defaultLat' => (float)Configuration::get('PS_STORES_CENTER_LAT'),
            'defaultLong' => (float)Configuration::get('PS_STORES_CENTER_LONG'),
            'searchUrl' => $this->context->link->getPageLink('stores'),
            'logo_store' => Configuration::get('PS_STORES_ICON')
        ));

        $this->setTemplate(_PS_THEME_DIR_.'stores.tpl');
    }
}
