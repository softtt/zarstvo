<?php

class OrderConfirmationController extends OrderConfirmationControllerCore
{
    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        FrontController::initContent();

        $this->context->smarty->assign(array(
            'is_guest' => $this->context->customer->is_guest,
            'HOOK_ORDER_CONFIRMATION' => $this->displayOrderConfirmation(),
            'HOOK_PAYMENT_RETURN' => $this->displayPaymentReturn()
        ));

        $this->context->smarty->assign(array(
            'id_order' => $this->id_order,
            'reference_order' => $this->reference,
            'id_order_formatted' => sprintf('#%06d', $this->id_order),
            'email' => $this->context->customer->email
        ));

        if ($this->context->customer->is_guest)
            $this->context->customer->mylogout();

        $order = new Order($this->id_order);
        $carrier = new Carrier((int)$order->id_carrier, (int)$order->id_lang);
        $address = new Address((int)$order->id_address_delivery);
        $dlv_adr_fields = AddressFormat::getOrderedAddressFields($address->id_country);
        $deliveryAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($address, $dlv_adr_fields);

        if (Validate::isLoadedObject($order))
        {
            $this->context->smarty->assign(array(
                'order' => $order,
                'order_details' => $order->getOrderDetailList(),
                'cart_rules' => $order->getCartRules(),
                'carrier' => $carrier,
                'point_of_delivery' => Store::getPointsOfDelivery()[0],
                'dlv_adr_fields' => $dlv_adr_fields,
                'deliveryAddressFormatedValues' => $deliveryAddressFormatedValues,
            ));
        }

        $this->setTemplate(_PS_THEME_DIR_.'order-confirmation.tpl');
    }
}
