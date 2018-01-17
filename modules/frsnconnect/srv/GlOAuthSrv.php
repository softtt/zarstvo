<?php
/**
 * Social Network connect modules
 * frsnconnect 0.15 by froZZen
 * 
 * Register application: https://code.google.com/apis/console/

 */

require_once 'frOAuth2Srv.php';

/**
 * Google provider class.
 */
class GlOAuthSrv extends frOAuth2Srv {	
	
    protected $scope = 'https://www.googleapis.com/auth/userinfo.email+https://www.googleapis.com/auth/userinfo.profile';
    protected $providerOptions = array(
        'authorize' => 'https://accounts.google.com/o/oauth2/auth',
	'access_token' => 'https://accounts.google.com/o/oauth2/token',
	);

    private function GetSex($sex) {
        switch (strtoupper($sex)){
            case 'female':
                return 2;
                break;
            case 'male':
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
    
        $info = (array)$this->makeSignedRequest('https://www.googleapis.com/oauth2/v1/userinfo');
				
	$this->attributes['id'] = $info['id'];
	$this->attributes['name'] = $info['name'];
                
	if (!empty($info['link']))
            $this->attributes['url'] = $info['link'];
		
 	$this->attributes['firstname'] = $info['given_name'];
	$this->attributes['lastname'] = $info['family_name'];
	
        $this->attributes['id_gender'] =  (!isset($info['gender']))? 9 : $this->GetSex($info['gender']);
	
         if (!empty($info['email']))
            $this->attributes['email'] =  $info['email'];

        $this->attributes['birthday'] =  (!isset($info['birthday']))? '' : $this->GetBDate($info['birthday']);

        // $this->errors[] = print_r($info, true);

    }

    protected function getCodeUrl($redirect_uri) {

        $redirect_state = '';   
        if ($this->hasState('redirect_params'))
            $redirect_state = $this->getState('redirect_params');
             
        if (strpos($redirect_uri, '?') !== false) {
            $url = explode('?', $redirect_uri);
            $url[1] = preg_replace('#[/]#', '%2F', $url[1]);
            $redirect_uri = $url[0];
            if (!strlen($redirect_state))
                $redirect_state .= 'state=snLogin_gl_id&'.$url[1];
	}

        $this->setState('redirect_uri', $redirect_uri);
        $this->setState('redirect_params', $redirect_state);
	$url = parent::getCodeUrl($redirect_uri);
        $url .= '&state='.strtr(base64_encode($redirect_state), '+/=', '-_,');

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

        $response =  $this->makeRequest($this->getTokenUrl($code), array('data' => $params));
        //$this->errors[] = print_r($response, true);               
                
        //$result = null;
        //return $result;
        
        return $response;       
        
    }
	
    /**
     * Save access token to the session.
     */
    protected function saveAccessToken($token) {

        $this->setState('auth_token', $token->access_token);
        $this->setState('expires', isset($token->expires_in) ? time() + (int)$token->expires_in - 60 : 0);
	$this->access_token = $token->access_token;                
                
    }
		
    /**
     * Makes the curl request to the url.
     */
    protected function makeRequest($url, $options = array(), $parseJson = true) {

        $options['query']['alt'] = 'json';
	
        return parent::makeRequest($url, $options, $parseJson);
        
    }
	
    /**
     * Returns the error info from json.
     */
    protected function fetchJsonError($json) {

        if (isset($json->error)) {
            return array(
                'code' => $json->error->code,
		'message' => $json->error->message,
		);
	}
	else
            return null;
        
    }
    
}