<?php

class OrderState extends OrderStateCore
{
    /**
    * Get all available order statuses
    *
    * @param integer $id_lang Language id for status name
    * @return array Order statuses
    */
    public static function getOrderStates($id_lang)
    {
        $cache_id = 'OrderState::getOrderStates_'.(int)$id_lang;
        if (!Cache::isStored($cache_id))
        {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT *
            FROM `'._DB_PREFIX_.'order_state` os
            LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int)$id_lang.')
            WHERE deleted = 0 AND hidden = 0
            ORDER BY `name` ASC');
            Cache::store($cache_id, $result);
        }
        return Cache::retrieve($cache_id);
    }
}
