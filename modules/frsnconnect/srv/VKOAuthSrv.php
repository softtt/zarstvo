<?php
/**
  * Social Network connect modules
  * frsnconnect 0.15 by froZZen
  *
 * Register application: http://vkontakte.ru/editapp?act=create&site=1
 * 
  */

require_once 'frOAuth2Srv.php';

/**
 * VKontakte provider class.
 */
class VKOAuthSrv extends frOAuth2Srv {	

    protected $client_id = '';
    protected $client_secret = '';
    protected $scope = 'notify';
    protected $providerOptions = array(
//        'authorize' => 'http://api.vk.com/oauth/authorize',
        'authorize' => 'https://oauth.vk.com/authorize',
//	'access_token' => 'https://api.vk.com/oauth/access_token'
	'access_token' => 'https://oauth.vk.com/access_token'
	);
    protected $uid = null;
        
    private function GetSex($sex) {
        switch ($sex){
            case 1:
                return 2;
                break;
            case 2:
                return 1;
                break;
            default:
                return 9;
        }  
    } 

    private function GetBDate($bdate) {
        // bdate format: "23.11.1981" or "21.9" (if year hide)
        $bday = explode('.', $bdate);
        $result = (!isset($bday[2]) ? '' : (int)($bday[2]).'-'.(int)($bday[1]).'-'.(int)($bday[0]));
        return $result; 
   }

    private function GetCountryName($country) {
        // http://vk.com/developers.php?oid=-1&p=getCountries       
        return -1;
    }

    private function GetCityName($city) {
        // http://vk.com/developers.php?oid=-1&p=getCities    
        
	if ($city == 0)
            return ' ';

        $info = (array)$this->makeSignedRequest('https://api.vk.com/method/places.getCityById', 
            array('query' => array(
                'cids' => $city
		),
            ));
	$info = $info['response'][0];
        
        if (isset($info))
            return $info->name;
        else 
            return '';
    }

    protected function fetchAttributes() {
        
        $info = (array)$this->makeSignedRequest(
            'https://api.vk.com/method/getProfiles', 
//'https://api.vk.com/method/users.get.json', 
            array(
                'query' => array(
                'uids' => $this->uid,
                'fields' => 'nickname, sex, bdate, city, country, timezone, photo, photo_medium, photo_big, photo_rec',
                ),
	));
	$info = $info['response'][0];

        //$this->errors[] = print_r($info, true);

	$this->attributes['id'] = $info->uid;
	$this->attributes['name'] = (!isset($info->nickname))? 'VK User' : $info->nickname;
	$this->attributes['url'] = 'http://vk.com/id'.$info->uid;
	$this->attributes['firstname'] = $info->first_name;
	$this->attributes['lastname'] = $info->last_name;
	$this->attributes['id_gender'] =  (!isset($info->sex))? 9 : $this->GetSex($info->sex);
//		$this->attributes['email'] =  '';
	$this->attributes['birthday'] =  (!isset($info->bdate))? '' : $this->GetBDate($info->bdate);
        $id_country = $this->GetCountryName($info->country);
        if ($id_country > 0) 
            $this->attributes['id_country'] = $id_country;
	if (isset($info->city)) 
           	$cityname = $this->GetCityName($info->city);
        if (strlen($cityname) > 0) 
            $this->attributes['city'] = $cityname;
        
//        if (isset($info->contacts)) {
//            foreach ($info->contacts as $key=>$value) {
//                if ($key == 'mobile_phone')
//                    $this->attributes['phone_mobile'] = $value;
//                if ($key == 'home_phone')
//                    $this->attributes['phone'] = $value;
//            }
//        }
    }
	
    /**
     * Returns the url to request to get OAuth2 code.
     */
    protected function getCodeUrl($redirect_uri) {
        
        if (strpos($redirect_uri, '?') !== false) {
            $url = explode('?', $redirect_uri);
            $url[1] = preg_replace('#[/]#', '%2F', $url[1]);
            $redirect_uri = implode('?', $url);
	}
		
	$this->setState('redirect_uri', $redirect_uri);
	$url = parent::getCodeUrl($redirect_uri).'&state=snLogin_vk_id';
	if (isset($_GET['js']))
            $url .= '&js=true';
		
	return $url;

    }

    protected function getTokenUrl($code) {
        
        return parent::getTokenUrl($code).'&redirect_uri='.urlencode($this->getState('redirect_uri'));
        
    }
	
    /**
     * Save access token to the session.
     */
    protected function saveAccessToken($token) {

        $this->setState('auth_token', $token->access_token);
	$this->setState('uid', $token->user_id);
	$this->setState('expires', time() + $token->expires_in - 60);
	$this->uid = $token->user_id;
	$this->access_token = $token->access_token;
        
    }
	
    /**
     * Restore access token from the session.
     * @return boolean whether the access token was successfuly restored.
     */
    protected function restoreAccessToken() {
        
        if ($this->hasState('uid') && parent::restoreAccessToken()) {
            $this->uid = $this->getState('uid');
        
            return true;
	}
	else {
            $this->uid = null;
            return false;
	}
        
    }

    /**
     * Returns the error info from json.
     */
    protected function fetchJsonError($json) {

        if (isset($json->error)) {
            return array(
                'code' => $json->error->error_code,
		'message' => $json->error->error_msg,
		);
	}
        else
            return null;
    }
    
}
