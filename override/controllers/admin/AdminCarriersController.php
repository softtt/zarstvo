<?php

class AdminCarriersController extends AdminCarriersControllerCore
{
    public function renderForm()
    {
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Carriers'),
                'icon' => 'icon-truck'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Company'),
                    'name' => 'name',
                    'required' => true,
                    'hint' => array(
                        sprintf($this->l('Allowed characters: letters, spaces and %s'), '().-'),
                        $this->l('Carrier name displayed during checkout'),
                        $this->l('For in-store pickup, enter 0 to replace the carrier name with your shop name.')
                    )
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Carrier is pickup'),
                    'name' => 'is_pickup',
                    'required' => false,
                    'hint' => $this->l('This carrier is pickup.'),
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
                ),
                array(
                    'type' => 'file',
                    'label' => $this->l('Logo'),
                    'name' => 'logo',
                    'hint' => $this->l('Upload a logo from your computer.').' (.gif, .jpg, .jpeg '.$this->l('or').' .png)'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Transit time'),
                    'name' => 'delay',
                    'lang' => true,
                    'required' => true,
                    'maxlength' => 128,
                    'hint' => $this->l('Estimated delivery time will be displayed during checkout.')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Speed grade'),
                    'name' => 'grade',
                    'required' => false,
                    'hint' => $this->l('Enter "0" for a longest shipping delay, or "9" for the shortest shipping delay.')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('URL'),
                    'name' => 'url',
                    'hint' => $this->l('Delivery tracking URL: Type \'@\' where the tracking number should appear. It will then be automatically replaced by the tracking number.')
                ),
                array(
                    'type' => 'checkbox',
                    'label' => $this->l('Zone'),
                    'name' => 'zone',
                    'values' => array(
                        'query' => Zone::getZones(false),
                        'id' => 'id_zone',
                        'name' => 'name'
                    ),
                    'hint' => $this->l('The zones in which this carrier will be used.')
                ),
                array(
                    'type' => 'group',
                    'label' => $this->l('Group access'),
                    'name' => 'groupBox',
                    'values' => Group::getGroups(Context::getContext()->language->id),
                    'hint' => $this->l('Mark the groups that are allowed access to this carrier.')
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Status'),
                    'name' => 'active',
                    'required' => false,
                    'class' => 't',
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
                    'hint' => $this->l('Enable the carrier in the Front Office.')
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Apply shipping cost'),
                    'name' => 'is_free',
                    'required' => false,
                    'class' => 't',
                    'values' => array(
                        array(
                            'id' => 'is_free_on',
                            'value' => 0,
                            'label' => '<img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" />'
                        ),
                        array(
                            'id' => 'is_free_off',
                            'value' => 1,
                            'label' => '<img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" />'
                        )
                    ),
                    'hint' => $this->l('Apply both regular shipping cost and product-specific shipping costs.')
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Tax'),
                    'name' => 'id_tax_rules_group',
                    'options' => array(
                        'query' => TaxRulesGroup::getTaxRulesGroups(true),
                        'id' => 'id_tax_rules_group',
                        'name' => 'name',
                        'default' => array(
                            'label' => $this->l('No Tax'),
                            'value' => 0
                        )
                    )
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Shipping and handling'),
                    'name' => 'shipping_handling',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'shipping_handling_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'shipping_handling_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                    'hint' => $this->l('Include the shipping and handling costs in the carrier price.')
                ),
                array(
                    'type' => 'radio',
                    'label' => $this->l('Billing'),
                    'name' => 'shipping_method',
                    'required' => false,
                    'class' => 't',
                    'br' => true,
                    'values' => array(
                        array(
                            'id' => 'billing_default',
                            'value' => Carrier::SHIPPING_METHOD_DEFAULT,
                            'label' => $this->l('Default behavior')
                        ),
                        array(
                            'id' => 'billing_price',
                            'value' => Carrier::SHIPPING_METHOD_PRICE,
                            'label' => $this->l('According to total price')
                        ),
                        array(
                            'id' => 'billing_weight',
                            'value' => Carrier::SHIPPING_METHOD_WEIGHT,
                            'label' => $this->l('According to total weight')
                        )
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Out-of-range behavior'),
                    'name' => 'range_behavior',
                    'options' => array(
                        'query' => array(
                            array(
                                'id' => 0,
                                'name' => $this->l('Apply the cost of the highest defined range')
                            ),
                            array(
                                'id' => 1,
                                'name' => $this->l('Disable carrier')
                            )
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    ),
                    'hint' => $this->l('Out-of-range behavior occurs when none is defined (e.g. when a customer\'s cart weight is greater than the highest range limit).')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Maximum package height'),
                    'name' => 'max_height',
                    'required' => false,
                    'hint' => $this->l('Maximum height managed by this carrier. Set the value to "0," or leave this field blank to ignore.')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Maximum package width'),
                    'name' => 'max_width',
                    'required' => false,
                    'hint' => $this->l('Maximum width managed by this carrier. Set the value to "0," or leave this field blank to ignore.')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Maximum package depth'),
                    'name' => 'max_depth',
                    'required' => false,
                    'hint' => $this->l('Maximum depth managed by this carrier. Set the value to "0," or leave this field blank to ignore.')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Maximum package weight'),
                    'name' => 'max_weight',
                    'required' => false,
                    'hint' => $this->l('Maximum weight managed by this carrier. Set the value to "0," or leave this field blank to ignore.')
                ),
                array(
                    'type' => 'hidden',
                    'name' => 'is_module'
                ),
                array(
                    'type' => 'hidden',
                    'name' => 'external_module_name',
                ),
                array(
                    'type' => 'hidden',
                    'name' => 'shipping_external'
                ),
                array(
                    'type' => 'hidden',
                    'name' => 'need_range'
                ),
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

        $this->fields_form['submit'] = array(
            'title' => $this->l('Save'),
        );

        if (!($obj = $this->loadObject(true)))
            return;

        $this->getFieldsValues($obj);
        return AdminController::renderForm();
    }

}
