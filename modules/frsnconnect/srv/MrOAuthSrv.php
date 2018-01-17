<?php
/**
 * Social Network connect modules
 * frsnconnect 0.15 by froZZen
 *
 * Register application: http://api.mail.ru/sites/my/add
 * 
 */

require_once 'frOAuth2Srv.php';

/**
 * Mail.Ru provider class.
 */
class MrOAuthSrv extends frOAuth2Srv {	
	
    protected $scope = '';
    protected $providerOptions = array(
        'authorize' => 'https://connect.mail.ru/oauth/authorize',
	'access_token' => 'https://connect.mail.ru/oauth/token',
	);
	
    protected $uid = null;

    private function GetSex($sex) {
        switch ($sex){
            case 1:
                return 2;
                break;
            case 0:
                return 1;
                break;
            default:
                return 9;
        }  
    } 

    private function GetBDate($bdate) {
        // birthday format: "15.02.1980"
        $bday = explode('.', $bdate);
        $result = (!isset($bday[2]) ? '' : (int)($bday[2]).'-'.(int)($bday[1]).'-'.(int)($bday[0]));
        return $result; 
   }
       
    protected function fetchAttributes() {

        $info = (array)$this->makeSignedRequest('http://www.appsmail.ru/platform/api', array(
            'query' => array(
            	'uids' => $this->uid,
		'method' => 'users.getInfo',
		'app_id' => $this->client_id,
		),
            ));
		
	$info = $info[0];
				
	$this->attributes['id'] = $info->uid;
	$this->attributes['name'] = $info->first_name.' '.$info->last_name;
	$this->attributes['url'] = $info->link;
                
        //$this->errors[] = print_r($info, true);               
                
 	$this->attributes['firstname'] = $info->first_name;
	$this->attributes['lastname'] = $info->last_name;
	$this->attributes['gender'] =  (!isset($info->sex))? 9 : $this->GetSex($info->sex);
	$this->attributes['email'] =  $info->email;
	$this->attributes['birthday'] =  (!isset($info->birthday))? '' : $this->GetBDate($info->birthday);
        if (isset($info->location)) {
            $location = (object) $info->location;  
            if (isset($location->city)) {
            $city = (object) $location->city;
            if (!is_null($city))
                $this->attributes['city'] = $city->name;
            }
        }
                
    }
	
    protected function getCodeUrl($redirect_uri) {

        if (strpos($redirect_uri, '?') !== false) {
            $url = explode('?', $redirect_uri);
            $url[1] = preg_replace('#[/]#', '%2F', $url[1]);
            $redirect_uri = implode('?', $url);
	}

        $redirect_uri .= '&state=snLogin_mr_id';
        
        $this->setState('redirect_uri', $redirect_uri);
		
	$url = parent::getCodeUrl($redirect_uri);
	if (isset($_GET['js']))
            $url .= '&js=true';
		
	return $url;
        
    }
	
    protected function getTokenUrl($code) {

        return $this->providerOptions['access_token'];
        
    }
	
    protected function getAccessToken($code) {

        $params = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->getState('redirect_uri'),
            );
	
        return $this->makeRequest($this->getTokenUrl($code), array('data' => $params));
        
    }
	
    /**
     * Save access token to the session.
     */
    protected function saveAccessToken($token) {

        $this->setState('auth_token', $token->access_token);
	$this->setState('uid', $token->x_mailru_vid);
	$this->setState('expires', time() + $token->expires_in - 60);
	$this->uid = $token->x_mailru_vid;
	$this->access_token = $token->access_token;
        
    }
	
    /**
     * Restore access token from the session.
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
	
    public function makeSignedRequest($url, $options = array(), $parseJson = true) {

        if (!$this->getIsAuthenticated())
            $this->errors[] = 'Unable to complete the request because the user was not authenticated.';
	
        $options['query']['secure'] = 1;
	$options['query']['session_key'] = $this->access_token;
	$_params = '';
	ksort($options['query']);
	foreach ($options['query'] as $k => $v) 
            $_params .= $k . '=' . $v;
	$options['query']['sig'] = md5($_params . $this->client_secret);
		
	$result = $this->makeRequest($url, $options);
	
        return $result;
        
    }
		
    /**
     * Returns the error info from json.
     */
    protected function fetchJsonError($json) {

        if (isset($json->error)) {
            return array(
                'code' => $json->error_code,
		'message' => $json->error_description,
		);
	}
	else
            return null;
    }
    
}