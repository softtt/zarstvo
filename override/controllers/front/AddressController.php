<?php

/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class AddressController extends AddressControllerCore
{
    public function postProcess()
    {
        if (Tools::isSubmit('autocomplete')) {

            $search_query = Tools::getValue('q');
            $field = Tools::getValue('field');
            $limit = Tools::getValue('limit', 10);

            $return = [];

            switch ($field) {
                case 'region':
                    $query = '
                        (SELECT DISTINCT region 
                        FROM ' . _DB_PREFIX_ . 'russian_post_indexes
                        WHERE region <> ""
                            AND region LIKE "%' . $search_query . '%"
                        LIMIT ' . $limit . ')
                        UNION
                        (SELECT DISTINCT autonom
                        FROM ' . _DB_PREFIX_ . 'russian_post_indexes
                        WHERE region = ""
                            AND autonom LIKE "%' . $search_query . '%")
                        ORDER BY region ASC';

                    $result = Db::getInstance()->executeS($query);

                    if (count($result)) {
                        foreach ($result as $item) {
                            $return[] = [
                                'text' => $item['region'],
                                'value' => $item['region'],
                            ];
                        }
                    }
                    break;

                case 'city':
                    $region = Tools::getValue('region');

                    $query = '
                        SELECT DISTINCT `city`, `region`, `autonom` 
                        FROM ' . _DB_PREFIX_ . 'russian_post_indexes 
                        WHERE (region = "' . $region . '" 
                            OR autonom = "' . $region . '")
                            AND (city LIKE "%' . $search_query . '%"
                                OR opsname LIKE "%' . $search_query . '%")
                        ORDER BY opsname 
                        LIMIT ' . $limit;

                    $result = Db::getInstance()->executeS($query);

                    if (count($result)) {
                        foreach ($result as $item) {
                            $text = $item['city'] != '' ? $item['city'] : $item['region'];

                            $return[] = [
                                'text' => $text,
                                'value' => $text,
                            ];
                        }
                    }
                    break;

                case 'postcode':
                    $city = Tools::getValue('city', '');

                    if (isset($city) && $city != '') {
                        $query = 'SELECT `index`, `opsname`, `city`, `region` 
                                    FROM ' . _DB_PREFIX_ . 'russian_post_indexes 
                                    WHERE (city = "' . $city . '" 
                                        OR region = "' . $city . '")
                                        AND `index` LIKE "%' . $search_query . '%"
                                    ORDER BY `index` 
                                    LIMIT ' . $limit;
                    } else {
                        $query = 'SELECT `index`, `opsname`, `city`, `region` 
                                    FROM ' . _DB_PREFIX_ . 'russian_post_indexes 
                                    WHERE `index` LIKE "%' . $search_query . '%"
                                    ORDER BY `index` 
                                    LIMIT ' . $limit;
                    }

                    $result = Db::getInstance()->executeS($query);

                    if (count($result)) {
                        foreach ($result as $item) {
                            $return[] = [
                                'text' => "{$item['opsname']}, {$item['index']}",
                                'value' => $item['index'],
                                'city' => $item['city'] != '' ? $item['city'] : $item['region'],
                                'region' => $item['region'],
                            ];
                        }
                    }
                    break;
            }

            die(Tools::jsonEncode($return));
        }

        parent::postProcess();
    }

    /**
     * Process changes on an address
     */
    protected function processSubmitAddress()
    {
        $address = new Address();
        $this->errors = $address->validateController();
        $address->id_customer = (int)$this->context->customer->id;

        // Check page token
        if ($this->context->customer->isLogged() && !$this->isTokenValid())
            $this->errors[] = Tools::displayError('Invalid token.');

        // Check phone
        if (!Tools::getValue('phone'))
            $this->errors[] = Tools::displayError('You must register phone number.');

        if ($address->id_country) {
            // Check country
            if (!($country = new Country($address->id_country)) || !Validate::isLoadedObject($country))
                throw new PrestaShopException('Country cannot be loaded with address->id_country');

            if (!$country->active)
                $this->errors[] = Tools::displayError('This country is not active.');

            $postcode = Tools::getValue('postcode');
            /* Check zip code format */
            if ($country->zip_code_format && !$country->checkZipCode($postcode))
                $this->errors[] = sprintf(Tools::displayError('The Zip/Postal code you\'ve entered is invalid. It must follow this format: %s'), str_replace('C', $country->iso_code, str_replace('N', '0', str_replace('L', 'A', $country->zip_code_format))));
            elseif (empty($postcode) && $country->need_zip_code)
                $this->errors[] = Tools::displayError('A Zip/Postal code is required.');
            elseif ($postcode && !Validate::isPostCode($postcode))
                $this->errors[] = Tools::displayError('The Zip/Postal code is invalid.');
        }

        // Check the requires fields which are settings in the BO
        $this->errors = array_merge($this->errors, $address->validateFieldsRequiredDatabase());

        // Don't continue this process if we have errors !
        if ($this->errors && !$this->ajax)
            return;


        if ($this->ajax && Configuration::get('PS_ORDER_PROCESS_TYPE')) {
            $this->errors = array_unique(array_merge($this->errors, $address->validateController()));
            if (count($this->errors)) {
                $return = array(
                    'hasError' => (bool)$this->errors,
                    'errors' => $this->errors
                );
                die(Tools::jsonEncode($return));
            }
        }


        if ($address_id = Tools::getValue('opc_id_address_delivery', false))
            if (Customer::customerHasAddress($this->context->customer->id, (int)$address_id))
                $address->id = $address_id;

        // Save address
        if ($address->id)
            $return = $address->update();
        else
            $return = $address->save();

        if ($return) {
            // Update id address of the current cart if necessary
            $this->context->cart->id_address_delivery = (int)$address->id;
            $this->context->cart->id_address_invoice = (int)$address->id;

            $this->context->cart->update();

            if ($this->ajax) {
                $return = array(
                    'hasError' => (bool)$this->errors,
                    'errors' => $this->errors,
                    'id_address_delivery' => (int)$address->id,
                    'id_address_invoice' => (int)$address->id
                );
                die(Tools::jsonEncode($return));
            }

            // Redirect to old page or current page
            if ($back = Tools::getValue('back')) {
                if ($back == Tools::secureReferrer(Tools::getValue('back')))
                    Tools::redirect(html_entity_decode($back));
                $mod = Tools::getValue('mod');
                Tools::redirect('index.php?controller=' . $back . ($mod ? '&back=' . $mod : ''));
            } else
                Tools::redirect('index.php?controller=addresses');
        }
        $this->errors[] = Tools::displayError('An error occurred while updating your address.');
    }
}
