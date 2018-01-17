<?php

class Carrier extends CarrierCore
{
    /** @var boolean Is carrier pickup */
    public $is_pickup = false;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'carrier',
        'primary' => 'id_carrier',
        'multilang' => true,
        'multilang_shop' => true,
        'fields' => array(
            /* Classic fields */
            'id_reference' =>           array('type' => self::TYPE_INT),
            'name' =>                   array('type' => self::TYPE_STRING, 'validate' => 'isCarrierName', 'required' => true, 'size' => 64),
            'active' =>                 array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'is_free' =>                array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'url' =>                    array('type' => self::TYPE_STRING, 'validate' => 'isAbsoluteUrl'),
            'shipping_handling' =>      array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'shipping_external' =>      array('type' => self::TYPE_BOOL),
            'range_behavior' =>         array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'shipping_method' =>        array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'max_width' =>              array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'max_height' =>             array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'max_depth' =>              array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'max_weight' =>             array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'grade' =>                  array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'size' => 1),
            'external_module_name' =>   array('type' => self::TYPE_STRING, 'size' => 64),
            'is_module' =>              array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'need_range' =>             array('type' => self::TYPE_BOOL),
            'position' =>               array('type' => self::TYPE_INT),
            'deleted' =>                array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'is_pickup' =>              array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),

            /* Lang fields */
            'delay' =>                  array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128),
        ),
    );
}
