<?php

class Export
{
    public $log = '';
    protected $dir = '';

    function __construct()
    {
        $this->addToLog('Export start');
        $this->dir = __DIR__ . '/../export_import/export/orders/';
    }


    public function exportOrder($orderID = null)
    {
        if (!$orderID)
            return;

        $data = '';
        $order = new Order($orderID);

        if (!$order->id)
            return;

        $date = DateTime::createFromFormat("Y-m-d H:i:s", $order->date_upd);
        $order_details = $order->getOrderDetailList();
        $carrier = new Carrier($order->id_carrier);
        $customer = $order->getCustomer();
        $address = new Address($order->id_address_delivery);
        if (isset(CustomerThread::getCustomerMessages($customer->id, null, $order->id)[0]))
            $message = str_replace(PHP_EOL, ' ', CustomerThread::getCustomerMessages($customer->id, null, $order->id)[0]['message']);
        else
            $message = '';
        $file = $this->dir . $date->format('Y_m_d_') . $order->reference . '.csv';

        $discount_card_code = '';
        if ($discount_card = $customer->getDiscountCard())
            $discount_card_code = $discount_card->getCardCode(true);

        foreach ($order_details as $product) {
            $data .= $customer->email . '^'
                   . $address->phone . '^'
                   . $address->city . '^'
                   . $address->lastname . '^'
                   . $address->firstname . '^'
                   . $discount_card_code . '^' # Customer discount ID - to be implemented
                   . $address->address1 . '^'
                   . $message . '^'
                   . (float)$product['total_price_tax_excl'] . '^'
                   . (int)$product['product_quantity'] . '^'
                   . (int)$product['product_price'] . '^'
                   . $carrier->name . '^'
                   . $product['product_name'] . '^'
                   . (int)$product['product_reference'] . '^'
                   . $order->reference . '^'
                   . $address->postcode . '^'
                   . (int)$order->total_discounts . '^'
                   . "\n";
        }

        if ($order->total_shipping > 0) {
          // Add delivery cost
          $data .= $customer->email . '^'
                 . $address->phone . '^'
                 . $address->city . '^'
                 . $address->lastname . '^'
                 . $address->firstname . '^'
                 . $discount_card_code . '^' # Customer discount ID - to be implemented
                 . $address->address1 . '^'
                 . $message . '^'
                 . (int)$order->total_shipping . '^'
                 . (int)1 . '^'
                 . (int)$order->total_shipping . '^'
                 . $carrier->name . '^'
                 . 'Доставка' . '^'
                 . '2425' . '^'
                 . $order->reference . '^'
                 . $address->postcode . '^'
                 . (int)$order->total_discounts . '^'
                 . "\n";
        }

        // Write data to .csv file
        file_put_contents($file, $data);
    }

    /**
     * Format given string and add it to log.
     *
     * @param string $string String to put in log.
     */
    public function addToLog($string)
    {
        $format = '[%s]: %s'.PHP_EOL;
        $this->log .= sprintf($format, date('H:i:s d.m.Y'), $string);
        return;
    }

    public function writeLogToFile()
    {
        file_put_contents(__DIR__ . '/../log/export_'.date("j.n.Y").'.txt', $this->log, FILE_APPEND);
        return;
    }
}
