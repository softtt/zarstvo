<?php

class Import
{
    public $log = '';

    function __construct()
    {
        $this->addToLog('Import start');
    }

    /**
     * Import products from .csv file into PrestaShop database.
     * Importing going into combinations or product.
     * sample file in 'export_import/import/IMPORTED/product_sample.csv'
     *
     * @param string $file Path to import file.
     */
    function importProducts($file)
    {
        $this->addToLog('-- Products import start');
        $imported = 0;

        // Read products file
        if ($products_file = fopen($file, 'r')) {
            $this->addToLog('-- Read file ' . $file);
            fgetcsv($products_file);

            while (($row = fgetcsv($products_file, 4000, ';')) !== false) {
                $reference = (int)$row[0];
                $name = $row[1];
                $price = $row[3];
                $quantity = (int)$row[4];

                // Find product by reference
                $sql = 'SELECT p.id_product FROM `'._DB_PREFIX_.'product` p
                    WHERE p.reference = '.$reference;
                $product_id = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);

                $sql = 'SELECT pa.id_product_attribute FROM `'._DB_PREFIX_.'product_attribute` pa
                        WHERE pa.reference = ' . $reference;
                $combination_id = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);

                if ($product_id and !$combination_id) {
                    $product = new Product($product_id);
                    $product->price = $price;
                    $product->update();

                    StockAvailable::setQuantity(
                        $product_id,
                        0,
                        $quantity
                    );
                    $imported++;

                } else {
                    // Try to find combination by reference id
                    if ($combination_id) {
                        // Update combination price and quantity
                        $combination = new Combination($combination_id);
                        $combination->price = $price;
                        $combination->wholesale_price = $price;
                        $combination->update();

                        // Set combination product price to 0
                        $product = new Product($combination->id_product);
                        $product->price = 0;
                        $product->update();

                        StockAvailable::setQuantity(
                            $combination->id_product,
                            $combination->id,
                            $quantity
                        );
                        $imported++;
                    }
                }
            }

            fclose($products_file);
        } else {
            $this->addToLog('ERROR: Could no open file ' . $file);
        }
        $this->addToLog('-- Products import end. Updated ' . $imported . ' products');
    }

    /**
     * Import discounts information from .csv file into PrestaShop database.
     * Importing going into overrided Customer class fields.
     * sample file in 'export_import/import/IMPORTED/discounts/sample.csv'
     *
     * @param string $file Path to import file.
     */
    function importDiscounts($file)
    {
        ini_set('max_execution_time', 0);

        $this->addToLog('-- Products import start');
        $imported = 0;
        // Read products file
        if ($discounts_file = fopen($file, 'r')) {
            $this->addToLog('-- Read file ' . $file);
            fgetcsv($discounts_file);

            while (($row = fgetcsv($discounts_file, 4000, ';')) !== false) {
                $title = (int)$row[0];
                $code = $row[1];
                $sum = floatval(str_replace(',', '.', $row[2]));
                $discount_percent = (int)$row[3];
                $email = $row[4];

                DiscountCard::clearDuplicates($email, $code);

                if (!$discount_code = DiscountCard::getDiscountCardByDiscountCardCode($code)) {
                    if ($email)
                        $discount_code = DiscountCard::getDiscountCardsByEmail($email);
                }

                if ($discount_code) {
                    $discount = new DiscountCard($discount_code[0]['id_customer_discount_card']);
                    if ($discount->is_virtual)
                        $discount->accumulated_online_before_offline = $discount->virtualAccumulatedSum();
                    $discount->discount_card_code = $code;
                    $discount->accumulated_sum = $sum;
                    $discount->fixed_discount_percent = $discount_percent;
                    $discount->is_virtual = false;
                    $discount->email = $email;

                    $discount->update();
                } else {
                    $discount = new DiscountCard;
                    $discount->email = $email;
                    $discount->discount_card_code = $code;
                    $discount->fixed_discount_percent = $discount_percent;
                    $discount->accumulated_sum = $sum;
                    $discount->is_virtual = false;

                    if ($email) {
                        $customer = new Customer;
                        $customer = $customer->getByEmail($email);

                        if ($customer) {
                            $discount->id_customer = $customer->id;
                            $discount->accumulated_online_before_offline = $customer->getStats()['total_orders'];
                        }
                    }

                    $discount->save();
                }

                $imported++;
            }

            fclose($discounts_file);

        } else {
            $this->addToLog('ERROR: Could no open file ' . $file);
        }
        $this->addToLog('-- Discounts import end. Updated ' . $imported . ' discounts');
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
        file_put_contents(__DIR__ . '/../export_import/log/import_'.date("j.n.Y").'.txt', $this->log, FILE_APPEND);
        return;
    }
}
