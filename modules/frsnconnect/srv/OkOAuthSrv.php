<?php
/**
  * Social Network connect modules
  * frsnconnect 0.15 by froZZen
  *
  * Register application: http://dev.odnoklassniki.ru/wiki/pages/viewpage.action?pageId=12878032 
  * http://www.odnoklassniki.ru/dk?st.cmd=appsInfoMyDevList&st._aid=Apps_Info_MyDev
  * Need 'VALUABLE ACCESS' scope
  * client_secret;client_public
*/

require_once 'frOAuth2Srv.php';

/**
 * Odnoklassniki provider class.
 */
class OkOAuthSrv extends frOAuth2Srv {	

    protected $client_id = '';
    protected $client_secret = '';
    protected $client_public = '';
    protected $scope = 'VALUABLE ACCESS';
    protected $providerOptions = array(
		'authorize' => 'http://www.odnoklassniki.ru/oauth/authorize',
		'access_token' => 'http://api.odnoklassniki.ru/oauth/token.do',
	);

    public function init($options = array()) {
        
        parent::init($options);

        $keys = explode(";", $this->client_secret);
        $this->client_secret = $keys[0];
        $this->client_public = $keys[1];
    }
        
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
        // birthday format: "1968-03-18"
        $bday = explode('-', $bdate);
        $result = (!isset($bday[0]) ? '' : (int)($bday[0]).'-'.(int)($bday[1]).'-'.(int)($bday[2]));
        return $result; 
   }
  
    protected function fetchAttributes() {
 
        //$this->errors[] = '64: '.$this->access_token.' - '.$this->client_secret;

        $sig = strtolower(md5(
                'application_key='.$this->client_public.
                'client_id='.$this->client_id.
                'format=JSON'.
                'method=users.getCurrentUser'.
                md5($this->access_token.$this->client_secret)));
		
        //$this->errors[] = '73: '.$sig.' - '.$this->client_public.' - '.$this->client_id ;

        $info = $this->makeRequest('http://api.odnoklassniki.ru/fb.do', 
            array('query' => array(
                'method' => 'users.getCurrentUser',
		'sig' => $sig,
                'format' => 'JSON',
                'application_key' => $this->client_public,
		'client_id' => $this->client_id,
                'access_token' => $this->access_token,
		),
            ));
        
        //$this->errors[] = print_r($info, true);
        //$this->errors[] = serialize($info);

        $sig2 = strtolower(md5(
            'application_key='.$this->client_public.
//                'client_id='.$this->client_id.
            'fields=location'.
            'format=JSON'.
//                'method=users.getInfo'.
            'uids='.$info->uid.
            md5($this->access_token.$this->client_secret)));

        $info2 = $this->makeRequest('http://api.odnoklassniki.ru/api/users/getInfo', 
            array('query' => array(
//                'method' => 'users.getCurrentUser',
		'sig' => $sig2,
                'format' => 'JSON',
                'fields' => 'location',
		'uids' => $info->uid,
                'application_key' => $this->client_public,
//                'session_key' => $this->client_public,
                'access_token' => $this->access_token,
		),
            ));
        //$this->errors[] = print_r($info2, true);
        $info2 = (object) $info2[0]; 
        //$this->errors[] = print_r($info2, true);

        $this->attributes['id'] = $info->uid;
	$this->attributes['name'] = (!isset($info->name))? 'OK User' : $info->name;
	$this->attributes['url'] = $info->link;
 	$this->attributes['firstname'] = $info->first_name;
	$this->attributes['lastname'] = $info->last_name;
	$this->attributes['id_gender'] =  (!isset($info->gender))? 9 : $this->GetSex($info->gender);
	$this->attributes['email'] =  $info->email;
	$this->attributes['birthday'] =  (!isset($info->birthday))? '' : $this->GetBDate($info->birthday);
        if (isset($info2->location)) {
            $location = (object) $info2->location;  
            $city = $location->city;
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

        $redirect_uri .= '&state=snLogin_ok_id';
        
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
        $url = $this->getTokenUrl($code).'?client_id='.$this->client_id.'&client_secret='.$this->client_secret.'&redirect_uri='.urlencode($this->getState('redirect_uri')).'&code='.$code.'&grant_type=authorization_code';

        //$this->errors[] = '176: '.$url;
        
        $result = $this->makeRequest($url, array('data' => $params));
        //$this->errors[] = '179: '.$result->access_token;
        
	return $result->access_token;
        
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
