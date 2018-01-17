<?php

class AdminStoresController extends AdminStoresControllerCore
{

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'store';
        $this->className = 'Store';
        $this->lang = false;
        $this->toolbar_scroll = false;

        $this->context = Context::getContext();

        if (!Tools::getValue('realedit'))
            $this->deleted = false;

        $this->fieldImageSettings = array(
            'name' => 'image',
            'dir' => 'st'
        );

        $this->fields_list = array(
            'id_store' => array('title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'),
            'name' => array('title' => $this->l('Name'), 'filter_key' => 'a!name'),
            'city' => array('title' => $this->l('City')),
            'address1' => array('title' => $this->l('Address'), 'filter_key' => 'a!address1'),
            'country' => array('title' => $this->l('Country'), 'filter_key' => 'cl!name'),
            'phone' => array('title' => $this->l('Phone')),
            'active' => array('title' => $this->l('Enabled'), 'align' => 'center', 'active' => 'status', 'type' => 'bool', 'orderby' => false),
            'is_point_of_delivery' => array('title' => $this->l('Point of delivery'), 'align' => 'center', 'active' => 'isPointOfDelivery', 'type' => 'bool', 'orderby' => false)
        );

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            )
        );

        $this->fields_options = array(
            'general' => array(
                'title' =>  $this->l('Parameters'),
                'fields' => array(
                    'PS_STORES_DISPLAY_FOOTER' => array(
                        'title' => $this->l('Display in the footer'),
                        'hint' => $this->l('Display a link to the store locator in the footer.'),
                        'cast' => 'intval',
                        'type' => 'bool'
                    ),
                    'PS_STORES_DISPLAY_SITEMAP' => array(
                        'title' => $this->l('Display in the sitemap page'),
                        'hint' => $this->l('Display a link to the store locator in the sitemap page.'),
                        'cast' => 'intval',
                        'type' => 'bool'
                    ),
                    'PS_STORES_SIMPLIFIED' => array(
                        'title' => $this->l('Show a simplified store locator'),
                        'hint' => $this->l('No map, no search, only a store directory.'),
                        'cast' => 'intval',
                        'type' => 'bool'
                    ),
                    'PS_STORES_CENTER_LAT' => array(
                        'title' => $this->l('Default latitude'),
                        'hint' => $this->l('Used for the initial position of the map.'),
                        'cast' => 'floatval',
                        'type' => 'text',
                        'size' => '10'
                    ),
                    'PS_STORES_CENTER_LONG' => array(
                        'title' => $this->l('Default longitude'),
                        'hint' => $this->l('Used for the initial position of the map.'),
                        'cast' => 'floatval',
                        'type' => 'text',
                        'size' => '10'
                    )
                ),
                'submit' => array('title' => $this->l('Save'))
            )
        );

        AdminController::__construct();

        $this->_buildOrderedFieldsShop($this->_getDefaultFieldsContent());
    }


    public function renderForm()
    {
        if (!($obj = $this->loadObject(true)))
            return;

        $image = _PS_STORE_IMG_DIR_.$obj->id.'.jpg';
        $image_url = ImageManager::thumbnail($image, $this->table.'_'.(int)$obj->id.'.'.$this->imageType, 350,
            $this->imageType, true, true);
        $image_size = file_exists($image) ? filesize($image) / 1000 : false;

        $tmp_addr = new Address();
        $res = $tmp_addr->getFieldsRequiredDatabase();
        $required_fields = array();
        foreach ($res as $row)
            $required_fields[(int)$row['id_required_field']] = $row['field_name'];

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Stores'),
                'icon' => 'icon-home'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Name'),
                    'name' => 'name',
                    'required' => false,
                    'hint' => array(
                        $this->l('Store name (e.g. City Center Mall Store).'),
                        $this->l('Allowed characters: letters, spaces and %s')
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Address'),
                    'name' => 'address1',
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Address (2)'),
                    'name' => 'address2'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Zip/postal Code'),
                    'name' => 'postcode',
                    'required' => in_array('postcode', $required_fields)
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('City'),
                    'name' => 'city',
                    'required' => true
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Country'),
                    'name' => 'id_country',
                    'required' => true,
                    'default_value' => (int)$this->context->country->id,
                    'options' => array(
                        'query' => Country::getCountries($this->context->language->id),
                        'id' => 'id_country',
                        'name' => 'name',
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('State'),
                    'name' => 'id_state',
                    'required' => true,
                    'options' => array(
                        'id' => 'id_state',
                        'name' => 'name',
                        'query' => null
                    )
                ),
                array(
                    'type' => 'latitude',
                    'label' => $this->l('Latitude / Longitude'),
                    'name' => 'latitude',
                    'required' => true,
                    'maxlength' => 12,
                    'hint' => $this->l('Store coordinates (e.g. 45.265469/-47.226478).')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Phone'),
                    'name' => 'phone'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Fax'),
                    'name' => 'fax'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Email address'),
                    'name' => 'email'
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Note'),
                    'name' => 'note',
                    'cols' => 42,
                    'rows' => 4
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Status'),
                    'name' => 'active',
                    'required' => false,
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                    'hint' => $this->l('Whether or not to display this store.')
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Point of delivery'),
                    'name' => 'is_point_of_delivery',
                    'required' => false,
                    'hint' => $this->l('This store is address of delivery'),
                    'values' => array(
                        array(
                            'id' => 'is_point_of_delivery_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'is_point_of_delivery_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'file',
                    'label' => $this->l('Picture'),
                    'name' => 'image',
                    'display_image' => true,
                    'image' => $image_url ? $image_url : false,
                    'size' => $image_size,
                    'hint' => $this->l('Storefront picture.')
                )
            ),
            'hours' => array(
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        );

        if (Shop::isFeatureActive())
        {
            $this->fields_form['input'][] = array(
                'type' => 'shop',
                'label' => $this->l('Shop association'),
                'name' => 'checkBoxShopAsso',
            );
        }

        $days = array();
        $days[1] = $this->l('Monday');
        $days[2] = $this->l('Tuesday');
        $days[3] = $this->l('Wednesday');
        $days[4] = $this->l('Thursday');
        $days[5] = $this->l('Friday');
        $days[6] = $this->l('Saturday');
        $days[7] = $this->l('Sunday');

        $hours = $this->getFieldValue($obj, 'hours');
        if (!empty($hours))
            $hours_unserialized = Tools::unSerialize($hours);

        $this->fields_value = array(
            'latitude' => $this->getFieldValue($obj, 'latitude') ? $this->getFieldValue($obj, 'latitude') : Configuration::get('PS_STORES_CENTER_LAT'),
            'longitude' => $this->getFieldValue($obj, 'longitude') ? $this->getFieldValue($obj, 'longitude') : Configuration::get('PS_STORES_CENTER_LONG'),
            'days' => $days,
            'hours' => isset($hours_unserialized) ? $hours_unserialized : false
        );

        return AdminController::renderForm();
    }



    public function postProcess()
    {
        if (isset($_POST['submitAdd'.$this->table]))
        {
            /* Cleaning fields */
            foreach ($_POST as $kp => $vp)
                if (!in_array($kp, array('checkBoxShopGroupAsso_store', 'checkBoxShopAsso_store')))
                    $_POST[$kp] = trim($vp);

            /* Rewrite latitude and longitude to 8 digits */
            $_POST['latitude'] = number_format((float)$_POST['latitude'], 8);
            $_POST['longitude'] = number_format((float)$_POST['longitude'], 8);

            /* If the selected country does not contain states */
            $id_state = (int)Tools::getValue('id_state');
            $id_country = (int)Tools::getValue('id_country');
            $country = new Country((int)$id_country);

            if ($id_country && $country && !(int)$country->contains_states && $id_state)
                $this->errors[] = Tools::displayError('You\'ve selected a state for a country that does not contain states.');

            /* If the selected country contains states, then a state have to be selected */
            if ((int)$country->contains_states && !$id_state)
                $this->errors[] = Tools::displayError('An address located in a country containing states must have a state selected.');

            $latitude = (float)Tools::getValue('latitude');
            $longitude = (float)Tools::getValue('longitude');

            if (empty($latitude) || empty($longitude))
               $this->errors[] = Tools::displayError('Latitude and longitude are required.');

            $postcode = Tools::getValue('postcode');
            /* Check zip code format */
            if ($country->zip_code_format && !$country->checkZipCode($postcode))
                $this->errors[] = Tools::displayError('Your Zip/postal code is incorrect.').'<br />'.Tools::displayError('It must be entered as follows:').' '.str_replace('C', $country->iso_code, str_replace('N', '0', str_replace('L', 'A', $country->zip_code_format)));
            elseif(empty($postcode) && $country->need_zip_code)
                $this->errors[] = Tools::displayError('A Zip/postal code is required.');
            elseif ($postcode && !Validate::isPostCode($postcode))
                $this->errors[] = Tools::displayError('The Zip/postal code is invalid.');

            /* Store hours */
            $_POST['hours'] = array();
            for ($i = 1; $i < 8; $i++)
                $_POST['hours'][] .= Tools::getValue('hours_'.(int)$i);
            $_POST['hours'] = serialize($_POST['hours']);
        }
        if (isset($_GET['isPointOfDelivery'.$this->table]))
            $this->processisPointOfDelivery();

        if (!count($this->errors))
            AdminStoresControllerCore::postProcess();
        else
            $this->display = 'add';
    }


    public function processisPointOfDelivery()
    {
        $store = new Store($this->id_object);
        if (!Validate::isLoadedObject($store))
            $this->errors[] = Tools::displayError('An error occurred while updating store information.');
        $store->is_point_of_delivery = $store->is_point_of_delivery ? 0 : 1;
        if (!$store->update())
            $this->errors[] = Tools::displayError('An error occurred while updating store information.');
        Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);
    }
}
