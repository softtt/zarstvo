<?php


class OrderHelperController extends FrontController
{
    public $php_self = 'order-helper';
    protected $display_header = false;
    protected $display_footer = false;

    public function postProcess()
    {
        // Autocomplete address fields
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
    }
}