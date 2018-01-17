<?php
/**
 * Social Network connect modules
 * frsnconnect 0.15 by froZZen
 *
 * Register application: https://dev.twitter.com/apps/new
 * 
 */

require_once 'frOAuthSrv.php';

/**
 * Twitter provider class.
 */
class TwOAuthSrv extends frOAuthSrv {	
	
			
    protected $providerOptions = array(
        'request' => 'https://api.twitter.com/oauth/request_token',
	'authorize' => 'https://api.twitter.com/oauth/authenticate', 
//		'authorize' => 'https://api.twitter.com/oauth/authorize',
	'access' => 'https://api.twitter.com/oauth/access_token',
	);
        
    public function init($options = array()) {
        
        $this->type = 'tw_id';
        parent::init($options);

    }	
    
    protected function fetchAttributes() {

        $info = $this->makeSignedRequest('https://api.twitter.com/1/account/verify_credentials.json');
                
	$this->attributes['id'] = $info->id;
	$this->attributes['name'] = (!isset($info->name))? 'Twitter User' : $info->name;

	$this->attributes['url'] = 'http://twitter.com/account/redirect_by_id?id='.$info->id_str;

        $this->attributes['city'] = $info->location;
	$this->attributes['id_gender'] =  9;
//	$this->attributes['email'] =  $info->email;

        $nn = explode(' ', trim($info->name));
        $this->attributes['firstname'] = $nn[0];
	$this->attributes['lastname'] = $nn[count($nn)-1];
//	$this->attributes['birthday'] =  (!isset($info->birthday))? '' : $this->GetBDate($info->birthday);
//           $this->attributes['id_country'] = $id_country;
                
//        $this->errors[] = print_r($info, true);          

    }
	
    /**
     * Authenticate the user.
     */
    public function authenticate() {

        if (isset($_GET['denied']))
            $this->cancel();
			
	return parent::authenticate();
	
    }
    
}