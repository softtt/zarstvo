<?php
/**
 * Social Network connect modules
 * frsnconnect 0.15 by froZZen
 */

require_once 'frOpenIDSrv.php';

/**
 * Yandex provider class.
 */
class YaOpIDSrv extends frOpenIDSrv {
	
    protected $url = 'http://openid.yandex.ru/';
    protected $requiredAttributes = array(
        'name' => array('fullname', 'namePerson'),
	'username' => array('nickname', 'namePerson/friendly'),
	'email' => array('email', 'contact/email'),
	'gender' => array('gender', 'person/gender'),
	'birthDate' => array('dob', 'birthDate'),
	);
	
    private function GetSex($sex) {
        
        switch (strtoupper($sex)){
            case 'F':
                return 2;
                break;
            case 'M':
                return 1;
                break;
            default:
                return 9;
        }  
    } 

    private function GetBDate($bdate) {
        // birthday format: "1968-03-18"
        $bday = explode('-', $bdate);
        $result = (!isset($bday[0]) ? '' : (int)($bday[0]).'-'.(int)($bday[1]).'-'.(int)($bday[2]));
        return $result; 
   }
	
   protected function fetchAttributes() {

        if (isset($this->attributes['username']) && !empty($this->attributes['username']))
            $this->attributes['url'] = 'http://openid.yandex.ru/'.$this->attributes['username'];

	$name = explode(' ', $this->attributes['name']);
 	$this->attributes['firstname'] = $name[0];
	$this->attributes['lastname'] = $name[count($name)-1];
        
	if (isset($this->attributes['gender']) && !empty($this->attributes['gender']))
            $this->attributes['gender'] =  $this->GetSex($this->attributes['gender']);
        else    
            $this->attributes['gender'] =  9;
        
	if (isset($this->attributes['birthDate']) && !empty($this->attributes['birthDate']))
            $this->attributes['birthday'] =  $this->GetBDate($this->attributes['birthDate']);
                
        //$this->errors[] = print_r($this->attributes, true);
        
    }
        
    public function setRedirectUrl($url) {

	if (isset($_GET['js']))
            $url .= '&js=true';
        
        parent::setRedirectUrl($url.'&state=snLogin_ya_id');

    }
        
}