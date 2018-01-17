<?php

class Store extends StoreCore
{
    /** @var bool Is address point of delivery for pickup */
    public $is_point_of_delivery;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'store',
        'primary' => 'id_store',
        'fields' => array(
            'id_country' =>                 array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_state' =>                   array('type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'),
            'name' =>                       array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 128),
            'address1' =>                   array('type' => self::TYPE_STRING, 'validate' => 'isAddress', 'required' => true, 'size' => 128),
            'address2' =>                   array('type' => self::TYPE_STRING, 'validate' => 'isAddress', 'size' => 128),
            'postcode' =>                   array('type' => self::TYPE_STRING, 'size' => 12),
            'city' =>                       array('type' => self::TYPE_STRING, 'validate' => 'isCityName', 'required' => true, 'size' => 64),
            'latitude' =>                   array('type' => self::TYPE_FLOAT, 'validate' => 'isCoordinate', 'size' => 13),
            'longitude' =>                  array('type' => self::TYPE_FLOAT, 'validate' => 'isCoordinate', 'size' => 13),
            'hours' =>                      array('type' => self::TYPE_STRING, 'validate' => 'isSerializedArray', 'size' => 254),
            'phone' =>                      array('type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 16),
            'fax' =>                        array('type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 16),
            'note' =>                       array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 65000),
            'email' =>                      array('type' => self::TYPE_STRING, 'validate' => 'isEmail', 'size' => 128),
            'active' =>                     array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'date_add' =>                   array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' =>                   array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'is_point_of_delivery' =>       array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
        ),
    );

    /**
     * Get points of delivery stores
     * @return array
     */
    public static function getPointsOfDelivery()
    {
        if ($result = Db::getInstance()->executeS('
            SELECT st.id_store, st.id_country, st.name, st.address1,
                   st.address2 as region, st.postcode, st.city
             FROM `'._DB_PREFIX_.'store` st
            WHERE `is_point_of_delivery` = 1'
        ))
            foreach ($result as &$row) {
                $address = explode(', ', $row['address1']);
                foreach ($address as $a) {
                    $data = explode('.', $a);
                    if ($data[0] == 'ул')
                        $row['street'] = $data[1];
                    elseif ($data[0] == 'д')
                        $row['house'] = $data[1];
                    elseif ($data[0] == 'к')
                        $row['housing'] = $data[1];
                }
            }

        return $result;
    }
}
