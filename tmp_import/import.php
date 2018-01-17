<?php 

include(dirname(__FILE__).'/../config/config.inc.php');
include(dirname(__FILE__).'/../init.php');

$file = 'product_weights.csv';

// Read products file
if ($products_file = fopen($file, 'r')) {
    fgetcsv($products_file);

    while (($row = fgetcsv($products_file, 4000, ',')) !== false) {
        list($title, $weight, $reference) = $row;

        if ($reference != '') {
            $weight = str_replace(',', '.', $weight);

            if ($weight != 'снято') {
                p("$title, $weight, $reference");

                $query1 = "UPDATE ps_product SET weight = {$weight} WHERE reference = {$reference}";
                $query3 = "UPDATE ps_product_attribute_shop pas left join ps_product_attribute pa on pa.id_product_attribute = pas.id_product_attribute SET pas.weight = {$weight}, pa.weight = {$weight} WHERE pa.reference = {$reference}";

                p($query1);
                p($query3);
                
                Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($query1);
                Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($query3);

                p('Product updated');
            }
        }
    }

    fclose($products_file);
}
