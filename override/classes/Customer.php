<?php

class Customer extends CustomerCore
{
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'customer',
        'primary' => 'id_customer',
        'fields' => array(
            'secure_key' =>                 array('type' => self::TYPE_STRING, 'validate' => 'isMd5', 'copy_post' => false),
            'lastname' =>                   array('type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 32),
            'firstname' =>                  array('type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 32),
            'email' =>                      array('type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 128),
            'passwd' =>                     array('type' => self::TYPE_STRING, 'validate' => 'isPasswd', 'required' => true, 'size' => 255),
            'last_passwd_gen' =>            array('type' => self::TYPE_STRING, 'copy_post' => false),
            'id_gender' =>                  array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'birthday' =>                   array('type' => self::TYPE_DATE, 'validate' => 'isBirthDate'),
            'newsletter' =>                 array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'newsletter_date_add' =>        array('type' => self::TYPE_DATE,'copy_post' => false),
            'ip_registration_newsletter' => array('type' => self::TYPE_STRING, 'copy_post' => false),
            'optin' =>                      array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'website' =>                    array('type' => self::TYPE_STRING, 'validate' => 'isUrl'),
            'company' =>                    array('type' => self::TYPE_STRING, 'validate' => 'isGenericName'),
            'siret' =>                      array('type' => self::TYPE_STRING, 'validate' => 'isSiret'),
            'ape' =>                        array('type' => self::TYPE_STRING, 'validate' => 'isApe'),
            'outstanding_allow_amount' =>   array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'copy_post' => false),
            'show_public_prices' =>         array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false),
            'id_risk' =>                    array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'copy_post' => false),
            'max_payment_days' =>           array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'copy_post' => false),
            'active' =>                     array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false),
            'deleted' =>                    array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false),
            'note' =>                       array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'size' => 65000, 'copy_post' => false),
            'is_guest' =>                   array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false),
            'id_shop' =>                    array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false),
            'id_shop_group' =>              array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false),
            'id_default_group' =>           array('type' => self::TYPE_INT, 'copy_post' => false),
            'id_lang' =>                    array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false),
            'date_add' =>                   array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
            'date_upd' =>                   array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
            'avatar' =>                     array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
        ),
    );

    public $avatar;

    public static $avatarSize = array(
        'full' => array(115, 115),
        'small' => array(60, 60)
    );

    public static $avatarType = array('jpeg', 'png', 'jpg');

    /** @var int access rights of created folders (octal) */
    protected static $access_rights = 0775;

    public function getAvatarPathCreation() {
        if (!$this->id)
            return false;

        $path = $this->getAvatarPath();
        $this->createAvatarFolder();

        return _PS_PROD_AVA_DIR_.$path;
    }

    public function createAvatarFolder()
    {
        if (!$this->id)
            return false;

        if (!file_exists(_PS_PROD_AVA_DIR_.$this->getAvatarPath()))
        {
            // Apparently sometimes mkdir cannot set the rights, and sometimes chmod can't. Trying both.
            @mkdir(_PS_PROD_AVA_DIR_.$this->getAvatarPath(), self::$access_rights, true);
            @chmod(_PS_PROD_AVA_DIR_.$this->getAvatarPath(), self::$access_rights);
        }
        return true;
    }


    public function getAvatarPath()
    {
        if (!$this->id)
            return false;

        $path = Image::getImgFolderStatic($this->id);

        return $path;
    }


    /**
     * Return customer instance from its e-mail (optionnaly check password)
     *
     * @param string $email e-mail
     * @param string $passwd Password is also checked if specified
     * @return Customer instance
     */
    public function getByEmail($email, $passwd = null, $ignore_guest = true)
    {
        if (!Validate::isEmail($email) || ($passwd && !Validate::isPasswd($passwd))) {
            return null;
        }
        $result = Db::getInstance()->getRow('
        SELECT *
        FROM `'._DB_PREFIX_.'customer`
        WHERE `email` = \''.pSQL($email).'\'
        '.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER).'
        '.(isset($passwd) ? 'AND `passwd` IN (\''.pSQL(Tools::encrypt($passwd)).'\', \''.sha1($passwd).'\')' : '').'
        AND `deleted` = 0
        '.($ignore_guest ? ' AND `is_guest` = 0' : ''));

        if (!$result)
            return false;
        $this->id = $result['id_customer'];
        foreach ($result as $key => $value)
            if (array_key_exists($key, $this))
                $this->{$key} = $value;

        return $this;
    }

    /**
     * Check if customer password is the right one
     *
     * @param string $passwd Password
     * @return boolean result
     */
    public static function checkPassword($id_customer, $passwd)
    {
        if (!Validate::isUnsignedId($id_customer) || !(!Validate::isMd5($passwd) || !Validate::isSha1($passwd)))
            die (Tools::displayError());
        $cache_id = 'Customer::checkPassword'.(int)$id_customer.'-'.$passwd;
        if (!Cache::isStored($cache_id))
        {
            $result = (bool)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
            SELECT `id_customer`
            FROM `'._DB_PREFIX_.'customer`
            WHERE `id_customer` = '.(int)$id_customer.'
            AND `passwd` IN (\''.pSQL($passwd).'\', \''.sha1($passwd).'\')');
            Cache::store($cache_id, $result);
        }
        return Cache::retrieve($cache_id);
    }

    public function getDiscountCard()
    {
        $query = '
            SELECT id_customer_discount_card
            FROM '._DB_PREFIX_.'customer_discount_card
            WHERE id_customer = '.(int)$this->id;

        $discount_id = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
        if ($discount_id)
            return new DiscountCard($discount_id);
        else
            return;
    }

    public function getUserAvatarPath($size='full') {
        if (!array_key_exists($size, self::$avatarSize)) {
            return false;
        }

        $path = Image::getImgFolderStatic($this->id);

        if (file_exists(_PS_PROD_AVA_DIR_.$path.'avatar_'.$size.'.jpg')) {
            return '/img/avatars/'.$path.'avatar_'.$size.'.jpg';
        } else {
            return $this->getDefaultAvatarPath($size);
        }
    }
    public function getUserAvatarPathFrontend($size='full') {
        if (!array_key_exists($size, self::$avatarSize)) {
            return false;
        }
        if (!$this->avatar) {
            return $this->getDefaultAvatarPath($size);
        }

        return $this->getUserAvatarPath($size);
    }

    public function getDefaultAvatarPath($size = 'full') {
        return '/img/default_avatar_'. $size.'.jpg';
    }

	/*
	* module: frsnconnect
	* date: 2016-09-13 14:38:04
	* version: 0.15.1
	*/
    public  $sn_service = array();


	/*
	* module: frsnconnect
	* date: 2016-09-13 14:38:04
	* version: 0.15.1
	*/
    public function getCustomerSnService() {

        $sn_servl = Context::getContext()->fr_sn_servlist;
        if (isset($sn_servl)) {
            $sql_id = (isset($this->id)) ? $this->id : -1;
            $sn_serv_customer = Db::getInstance()->ExecuteS('
                SELECT id_sn_service, sn_id
                FROM '._DB_PREFIX_.'sn_customer
                WHERE id_customer='.(int)$sql_id
            );
            if (!empty($sn_servl)) {
                foreach ($sn_servl AS $key=>$sn_serv) {
                    $sn_id = '';
                    if ($sn_serv_customer) {
                        foreach ($sn_serv_customer AS $sn_serv_cust)
                            if ($sn_serv_cust['id_sn_service'] == $sn_serv['id_sn_service'])
                                $sn_id = $sn_serv_cust['sn_id'];
                    }
                    $this->sn_service[$key] = $sn_id;
                }
            }
        }

    }


	/*
	* module: frsnconnect
	* date: 2016-09-13 14:38:04
	* version: 0.15.1
	*/

	/*
	* module: frsnconnect
	* date: 2016-09-13 14:38:04
	* version: 0.15.1
	*/
    public function deleteCustomerSnAccount($sn) {

        $result = true;
        $where = '`id_customer` = '.(int)($this->id).' AND `id_sn_service` = '.(int)($sn);
        $result = Db::getInstance()->Execute('DELETE FROM `'.pSQL(_DB_PREFIX_.'sn_customer').'` WHERE '.$where);

        return $result;

    }


	/*
	* module: frsnconnect
	* date: 2016-09-13 14:38:04
	* version: 0.15.1
	*/
    public function addCustomerSnAccount($sn, $id_sn) {

        $result = true;
        $result = Db::getInstance()->AutoExecute(_DB_PREFIX_.'sn_customer', array('id_customer'=>(int)($this->id),'id_sn_service'=>(int)($sn),'sn_id'=>$id_sn), 'INSERT');

        return $result;

    }


	/*
	* module: frsnconnect
	* date: 2016-09-13 14:38:04
	* version: 0.15.1
	*/
    public function updateCustomerSnAccount($sn, $id_sn) {

        $result = true;
        $where = '`id_customer` = '.(int)($this->id).' AND `id_sn_service` = '.(int)($sn);
        $result = Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'sn_customer WHERE '.$where);
        if ($result)
            $result = Db::getInstance()->AutoExecute(_DB_PREFIX_.'sn_customer', array('sn_id'=>$id_sn), 'UPDATE', $where);
        else
            $result = $this->addCustomerSnAccount ($sn, $id_sn);
        return $result;

    }


	/*
	* module: frsnconnect
	* date: 2016-09-13 14:38:04
	* version: 0.15.1
	*/
    public function getBySNId($sn_id, $sn_user_id) {

        $result = Db::getInstance()->getRow('
            SELECT c.*
            FROM  `'._DB_PREFIX_.'customer` AS c
            LEFT JOIN  `'._DB_PREFIX_.'sn_customer` AS s
            USING (id_customer )
            WHERE c.`active` =1
            AND c.`deleted` =0
            AND c.`is_guest` =0
            AND s.`id_sn_service` ='.trim($sn_id).'
            AND s.`sn_id` ="'.trim($sn_user_id).'"'
        );
        if (!$result)
            return false;
	$this->id = $result['id_customer'];
	foreach ($result AS $key => $value)
            if (key_exists($key, $this))
                $this->{$key} = $value;
//        $this->getCustomerSnService();
	return $this;
    }


	/*
	* module: frsnconnect
	* date: 2016-09-13 14:38:04
	* version: 0.15.1
	*/
    public function logout() {

	if (isset(Context::getContext()->cookie)) {
            $cookies = Context::getContext()->cookie;
            $cookies->unsetFamily('__snconnect_');
            $cookies->logout();
        }

        $this->logged = 0;

    }


	/*
	* module: frsnconnect
	* date: 2016-09-13 14:38:04
	* version: 0.15.1
	*/
    public function mylogout() {

	if (isset(Context::getContext()->cookie)) {
            $cookies = Context::getContext()->cookie;
            $cookies->unsetFamily('__snconnect_');
            $cookies->mylogout();
        }

        $this->logged = 0;

    }


	/*
	* module: frsnconnect
	* date: 2016-09-13 14:38:04
	* version: 0.15.1
	*/
    public function delete() {

        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'sn_customer` WHERE `id_customer` = '.(int)($this->id));
        return parent::delete();

    }

}
