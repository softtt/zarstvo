<?php
/**
  * Social Network connect modules
  * frsnconnect 0.15 by froZZen
  */

class Customer extends CustomerCore {

    public $sn_service = array();

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

    public function deleteCustomerSnAccount($sn) {
    
        $result = true;
        $where = '`id_customer` = '.(int)($this->id).' AND `id_sn_service` = '.(int)($sn);
        $result = Db::getInstance()->Execute('DELETE FROM `'.pSQL(_DB_PREFIX_.'sn_customer').'` WHERE '.$where);
        
        return $result;
       
    }
    
    public function addCustomerSnAccount($sn, $id_sn) {
    
        $result = true;
        $result = Db::getInstance()->AutoExecute(_DB_PREFIX_.'sn_customer', array('id_customer'=>(int)($this->id),'id_sn_service'=>(int)($sn),'sn_id'=>$id_sn), 'INSERT');
       
        return $result;
        
    }    

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

    public function logout() {
        
	if (isset(Context::getContext()->cookie)) {
            $cookies = Context::getContext()->cookie;
            $cookies->unsetFamily('__snconnect_');
            $cookies->logout();
        }        

        $this->logged = 0;
	
    }

    public function mylogout() {
        
	if (isset(Context::getContext()->cookie)) {
            $cookies = Context::getContext()->cookie;
            $cookies->unsetFamily('__snconnect_');
            $cookies->mylogout();
        }        
	
        $this->logged = 0;
        
    }

    public function delete() {
    
        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'sn_customer` WHERE `id_customer` = '.(int)($this->id));
        return parent::delete();
        
    }
        
}

?>