<?php

class AdminCartsController extends AdminCartsControllerCore
{
    protected function getDeliveryOptionList()
    {
        $delivery_option_list_formated = array();
        $delivery_option_list = $this->context->cart->getDeliveryOptionList();

        if (!count($delivery_option_list))
            return array();

        $id_default_carrier = (int)Configuration::get('PS_CARRIER_DEFAULT');
        foreach (current($delivery_option_list) as $key => $delivery_option)
        {
            $name = '';
            $first = true;
            $id_default_carrier_delivery = false;
            foreach ($delivery_option['carrier_list'] as $carrier)
            {
                if (!$first)
                    $name .= ', ';
                else
                    $first = false;

                $name .= $carrier['instance']->name;

                // if ($delivery_option['unique_carrier'])
                //  $name .= ' - '.$carrier['instance']->delay[$this->context->employee->id_lang];

                if (!$id_default_carrier_delivery)
                    $id_default_carrier_delivery = (int)$carrier['instance']->id;
                if ($carrier['instance']->id == $id_default_carrier)
                    $id_default_carrier_delivery = $id_default_carrier;
                if (!$this->context->cart->id_carrier)
                {
                    $this->context->cart->setDeliveryOption(array($this->context->cart->id_address_delivery => (int)$carrier['instance']->id.','));
                    $this->context->cart->save();
                }
            }
            $delivery_option_list_formated[] = array('name' => $name, 'key' => $key);
        }
        return $delivery_option_list_formated;
    }
}
