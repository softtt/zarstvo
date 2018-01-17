<?php

class Address extends AddressCore
{
    public static function getFirstCustomerAddressId($id_customer, $active = true)
    {
        if (!$id_customer)
            return false;
        $result = (int)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
            SELECT `id_address`
            FROM `'._DB_PREFIX_.'address`
            WHERE `id_customer` = '.(int)$id_customer.' AND `deleted` = 0'.($active ? ' AND `active` = 1' : '')
        );
        Cache::store($cache_id, $result);
        return $result;
    }
}
