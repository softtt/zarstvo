<?php

class DiscountCard extends ObjectModel
{
    /** @var integer Customer id which discount card belongs to */
    public $id_customer = null;

    /** @var string Email of customer discount code is connected to */
    public $email;

    /** @var string Customer discount phisical card code */
    public $discount_card_code;

    /** @var string Fixed customer discount percent */
    public $fixed_discount_percent;

    /** @var float Total sum of customers orders in offline stores */
    public $accumulated_sum = 0;

    /** @var boolean Is discount card virtual */
    public $is_virtual = true;

    /** @var string Customer discount code in online store */
    public $virtual_discount_code;

    /** @var string Total accumulated sum online before customer registered offline card */
    public $accumulated_online_before_offline = 0;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'customer_discount_card',
        'primary' => 'id_customer_discount_card',
        'fields' => array(
            'id_customer' =>                       array('type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false),
            'email' =>                             array('type' => self::TYPE_STRING, 'copy_post' => false),
            'discount_card_code' =>                array('type' => self::TYPE_STRING),
            'fixed_discount_percent' =>            array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false),
            'accumulated_sum' =>                   array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'copy_post' => false),
            'is_virtual' =>                        array('type' => self::TYPE_BOOL),
            'virtual_discount_code' =>             array('type' => self::TYPE_STRING, 'copy_post' => false),
            'accumulated_online_before_offline' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'copy_post' => false),
        ),
    );

    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    /**
     * Return discount code by personal customer code on site
     *
     * @static
     * @param $virtual_discount_code
     * @return int
     */
    public static function getDiscountCardByPersonalDiscountCode($virtual_discount_code)
    {
        $sql = 'SELECT *
                FROM `'._DB_PREFIX_.'customer_discount_card`
                WHERE `virtual_discount_code` = \''.pSQL($virtual_discount_code).'\'';
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Return discount code by discount card code
     *
     * @static
     * @param $discount_card_code
     * @return int
     */
    public static function getDiscountCardByDiscountCardCode($discount_card_code)
    {
        $sql = 'SELECT id_customer_discount_card
                FROM `'._DB_PREFIX_.'customer_discount_card`
                WHERE `discount_card_code` = \''.pSQL($discount_card_code).'\'';

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Generate a unique discount card code
     *
     * @return String
     */
    public static function generatePersonalDiscountCode()
    {
        return strtoupper(Tools::passwdGen(9, 'ALPHANUMERIC'));
    }


    /**
     * Retrieve discount cards by email address
     *
     * @static
     * @param $email
     * @return array
     */
    public static function getDiscountCardsByEmail($email)
    {
        $sql = 'SELECT id_customer_discount_card
                FROM `'._DB_PREFIX_.'customer_discount_card`
                WHERE `email` = \''.pSQL($email) . '\'';

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Get customer accumulated sum on virtual card.
     *
     * @return float
     */
    public function virtualAccumulatedSum()
    {
        if ($customer = new Customer($this->id_customer))
            return $customer->getStats()['total_orders'];
        else
            return 0;
    }

    /**
     * Return discount card code, virtual or real.
     *
     * @return string
     */
    public function getCardCode($real = false)
    {
        if (!$real && $this->is_virtual)
            return $this->virtual_discount_code;
        else
            return $this->discount_card_code;
    }

    /**
     * Return discount card accumulated sum, virtual or real.
     *
     * @return float
     */
    public function getAccumulatedSum()
    {
        if ($this->is_virtual)
            return $this->virtualAccumulatedSum();
        else
            return $this->accumulated_sum + $this->accumulated_online_before_offline;
    }

    /**
     * Get available CartRule for this discount based on accumulated minimum amount.
     * CartRule should have is_discount_cart_rule = true.
     *
     * @return CartRule
     */
    public function getDiscountCartRule($active = true)
    {
        if ($accumulated_sum = $this->getAccumulatedSum())
        {
            $result =  Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                SELECT `id_cart_rule`, `reduction_percent`
                FROM `'._DB_PREFIX_.'cart_rule`
                WHERE `is_discount_cart_rule` = 1
                AND `accumulated_minimum_amount` <= '.$accumulated_sum.'
                AND date_from < "'.date('Y-m-d H:i:s').'"
                AND date_to > "'.date('Y-m-d H:i:s').'"
                '.($active ? 'AND `active` = 1' : '').'
                ORDER BY `reduction_percent` DESC
            ');

            if ($rule = $result[0])
                return new CartRule($rule['id_cart_rule']);
            else
                return null;
        }
    }

    /**
     * Get Discount card percent based on CartRules or card fixed_discount_percent
     * @param string $value [description]
     */
    public function getDiscountPercent()
    {
        if ($this->fixed_discount_percent)
            return $this->fixed_discount_percent;
        elseif ($cart_rule = $this->getDiscountCartRule())
            return $cart_rule->reduction_percent;
    }

    /**
     * Clear Discount cards duplicates on some reasons appeared in database
     * @param  string $email
     * @param  string $code
     * @return bool
     */
    public static function clearDuplicates($email, $code)
    {
        if ($code || $email) {
            $sql = "SELECT * FROM `"._DB_PREFIX_."customer_discount_card`
                    WHERE email = '$email'
                    OR `discount_card_code` = '$code'";

            $cards = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

            if (count($cards) > 1) {
                foreach ($cards as $card) {
                    $discount = new DiscountCard($card['id_customer_discount_card']);
                    $discount->delete();
                }
            }
            return true;
        } else {
            return false;
        }
    }
}
