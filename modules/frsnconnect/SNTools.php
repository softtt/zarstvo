<?php
/**
  * Social Network connect modules
  * frsnconnect 0.15 by froZZen
  */

class SNTools {

    static public function GetSNServiceList() {

        $tmp = Db::getInstance()->ExecuteS('
            SELECT id_sn_service, sn_service_name, sn_service_name_full
            FROM '._DB_PREFIX_.'sn_service
            WHERE active = 1    
            ORDER BY id_sn_service');
        $result = NULL;      
        if ($tmp) {
            foreach ($tmp as $value) {
                $result[$value['sn_service_name'].'_id'] = array(
                    'id_sn_service' => $value['id_sn_service']
                    , 'sn_service_name' => $value['sn_service_name']
                    , 'sn_service_name_full' => $value['sn_service_name_full']
                    );  
            }
        }
        return $result;
    }

    static public function GetSNServiceListSetup() {
        $result = Db::getInstance()->ExecuteS('
            SELECT *
            FROM '._DB_PREFIX_.'sn_service
            ORDER BY id_sn_service');

        return $result;
    }

    static public function GetSNServiceID($sn_id) {
        $service = self::getIdentity($sn_id);
        return $service;
    }          
    
    static function getIdentity($sn_id) {
        $service = Db::getInstance()->getRow('
            SELECT 
            id_sn_service
            , sn_service_name
            , sn_service_name_full
            , sn_service_key_id
            , sn_service_key_secret
            , class
            FROM '._DB_PREFIX_.'sn_service
            WHERE active = 1    
            AND id_sn_service = '.(int)$sn_id);
        if ($service) {
            $class = $service['class'];
            require_once dirname(__FILE__).'/srv/'.$class.'.php';
            $identity = new $class();
            $identity->init($service);
            return $identity;
        }
    }
}