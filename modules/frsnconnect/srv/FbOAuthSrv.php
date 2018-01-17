<?php
/**
  * Social Network connect modules
  * frsnconnect 0.15 by froZZen
  *
  * Register application: https://developers.facebook.com/apps/
  * 
*/

require_once 'frOAuth2Srv.php';

/**
 * Facebook provider class.
 */
class FbOAuthSrv extends frOAuth2Srv {	

    protected $client_id = '';
    protected $client_secret = '';
    protected $scope = 'email,user_birthday,user_location,user_about_me';
    protected $providerOptions = array(
        'authorize' => 'https://www.facebook.com/dialog/oauth',
	'access_token' => 'https://graph.facebook.com/oauth/access_token'
	);

    private function GetSex($sex) {
        switch ($sex){
            case 2:
                return 2;
                break;
            case 1:
                return 1;
                break;
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
        // birthday format: "03/18/1968"
        $bday = explode('/', $bdate);
        $result = (!isset($bday[2]) ? '' : (int)($bday[2]).'-'.(int)($bday[0]).'-'.(int)($bday[1]));
        return $result; 
   }
  
    protected function fetchAttributes() {
        $info = (object) $this->makeSignedRequest('https://graph.facebook.com/me');
        // http://developers.facebook.com/docs/reference/api/user/
 	$this->attributes['id'] = $info->id;
	$this->attributes['name'] = (!isset($info->name))? 'FB User' : $info->name;
	$this->attributes['url'] = $info->link;
 	$this->attributes['firstname'] = $info->first_name;
	$this->attributes['lastname'] = $info->last_name;
	$this->attributes['gender'] =  (!isset($info->gender))? 9 : $this->GetSex($info->gender);
	$this->attributes['email'] =  $info->email;
	$this->attributes['birthday'] =  (!isset($info->birthday))? '' : $this->GetBDate($info->birthday);
        if (isset($info->location)) {
            $location = (object) $info->location;  
            $city = $location->name;
            if (strlen($city) > 0) 
                $this->attributes['city'] = $city;
        }
    }

    protected function getCodeUrl($redirect_uri) {
        if (strpos($redirect_uri, '?') !== false) {
            $url = explode('?', $redirect_uri);
            $url[1] = preg_replace('#[/]#', '%2F', $url[1]);
            $redirect_uri = implode('?', $url);
	}
		
	$this->setState('redirect_uri', $redirect_uri);
	$url = parent::getCodeUrl($redirect_uri).'&state=snLogin_fb_id';
	if (isset($_GET['js']))
            $url .= '&js=true';
		
	return $url;
    }
	
    protected function getTokenUrl($code) {
        return parent::getTokenUrl($code).'&redirect_uri='.urlencode($this->getState('redirect_uri'));
    }
	
    protected function getAccessToken($code) {
        $response = $this->makeRequest($this->getTokenUrl($code), array(), false);
	parse_str($response, $result);
	return $result;
    }
		
    /**
     * Save access token to the session.
     */
    protected function saveAccessToken($token) {
        $this->setState('auth_token', $token['access_token']);
        $this->setState('expires', isset($token['expires']) ? time() + (int)$token['expires'] - 60 : 0);
	$this->access_token = $token['access_token'];
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