<?php

class AdminCarrierWizardController extends AdminCarrierWizardControllerCore
{
    public function renderStepOne($carrier)
    {
        $this->fields_form = array(
            'form' => array(
                'id_form' => 'step_carrier_general',
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Carrier name'),
                        'name' => 'name',
                        'required' => true,
                        'hint' => array(
                            sprintf($this->l('Allowed characters: letters, spaces and "%s".'), '().-'),
                            $this->l('The carrier\'s name will be displayed during checkout.'),
                            $this->l('For in-store pickup, enter 0 to replace the carrier name with your shop name.')
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Carrier is pickup'),
                        'name' => 'is_pickup',
                        'required' => false,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'is_pickup_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'is_pickup_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                        'hint' => $this->l('This carrier is pickup.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Transit time'),
                        'name' => 'delay',
                        'lang' => true,
                        'required' => true,
                        'maxlength' => 128,
                        'hint' => $this->l('The estimated delivery time will be displayed during checkout.')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Speed grade'),
                        'name' => 'grade',
                        'required' => false,
                        'size' => 1,
                        'hint' => $this->l('Enter "0" for a longest shipping delay, or "9" for the shortest shipping delay.')
                    ),
                    array(
                        'type' => 'logo',
                        'label' => $this->l('Logo'),
                        'name' => 'logo'
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Tracking URL'),
                        'name' => 'url',
                        'hint' => $this->l('Delivery tracking URL: Type \'@\' where the tracking number should appear. It will be automatically replaced by the tracking number.')
                    ),
                )),
        );

        $tpl_vars = array('max_image_size' => (int)Configuration::get('PS_PRODUCT_PICTURE_MAX_SIZE') / 1024 / 1024);
        $fields_value = $this->getStepOneFieldsValues($carrier);
        return $this->renderGenericForm(array('form' => $this->fields_form), $fields_value, $tpl_vars);
    }

    public function getStepOneFieldsValues($carrier)
    {
        return array(
            'id_carrier' => $this->getFieldValue($carrier, 'id_carrier'),
            'name' => $this->getFieldValue($carrier, 'name'),
            'is_pickup' => $this->getFieldValue($carrier, 'is_pickup'),
            'delay' => $this->getFieldValue($carrier, 'delay'),
            'grade' => $this->getFieldValue($carrier, 'grade'),
            'url' => $this->getFieldValue($carrier, 'url'),
        );
    }
}
