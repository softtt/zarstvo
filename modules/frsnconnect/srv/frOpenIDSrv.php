<?php
/**
  * Social Network connect modules
  * frsnconnect 0.15 by froZZen
 */

require_once 'frSNSrvBase.php';
require_once 'lib/LightOpenID.php';

/**
 * base class for all OpenID providers.
 */
abstract class frOpenIDSrv extends frSNSrvBase  {
	
    protected $type = 'OpenID';
    /**
     * @var LightOpenID the openid library instance.
     */
    private $auth;
	
    /**
     * @var string the OpenID authorization url.
     */
    protected $url;
	
    /**
     * @var array the OpenID required attributes.
     */
    protected $requiredAttributes = array();
	
    /**
     * Initialize the component.
     * @param EAuth $component the component instance.
     * @param array $options properties initialization.
     */
    public function init($options = array()) {

        parent::init($options);
    	
        $this->auth = new LightOpenID();
        if(count($options)) 
            foreach ($options as $key => $val)
                $this->auth->$key = $val;
 
        $server = Tools::getHttpHost(true);
        if(isset($_SERVER['HTTP_X_REWRITE_URL'])) // IIS
            $path = $_SERVER['HTTP_X_REWRITE_URL'];
        else if(isset($_SERVER['REQUEST_URI'])) {
            $path = $_SERVER['REQUEST_URI'];
            if(!empty($_SERVER['HTTP_HOST'])) {
                if(strpos($path, $_SERVER['HTTP_HOST'])!==false)
                    $path = preg_replace('/^\w+:\/\/[^\/]+/','', $path);
            }
            else
                $path = preg_replace('/^(http|https):\/\/[^\/]+/i','', $path);
        }
                
        $redirect_uri = $server.$path;
        $this->setRedirectUrl($redirect_uri);
        
    }
		
    /**
     * Authenticate the user.
     * @return boolean whether user was successfuly authenticated.
     */
    public function authenticate() { 
    
        if (!empty($_REQUEST['openid_mode'])) {
            switch ($_REQUEST['openid_mode']) {
                case 'id_res':
                    try {
                        if ($this->auth->validate()) {
                            $this->attributes['id'] = $this->auth->identity;
		
                            $attributes = $this->auth->getAttributes();
                            foreach ($this->requiredAttributes as $key => $attr) {
                                if (isset($attributes[$attr[1]])) {
                                    $this->attributes[$key] = $attributes[$attr[1]];
                                }
				else {
                                    $this->errors[] = 'Unable to complete the authentication because the required data was not received.';
                                    return false;
				}
                            }
                            $this->authenticated = true;
                            return true;
			}
			else {
                            $this->errors[] = 'Unable to complete the authentication because the required data was not received.';
                            return false;
			}
                    }
                    catch (Exception $e) {
                        $this->errors[] = $e->getCode().$e->getMessage();
                    }
                    break;
				
		case 'cancel':
                    //$this->cancel();
                    $this->errors[] = Tools::displayError('The user has refused to authorize the application!');
                    break;
				
		default: 
                    $this->errors[] = '400 - Your request is invalid.';
                    break;
            }
	} 
	else {
            $this->auth->identity = $this->url; //Setting identifier
            $this->auth->required = array(); //Try to get info from openid provider
            foreach ($this->requiredAttributes as $attribute)
                $this->auth->required[$attribute[0]] = $attribute[1];
                        
            $this->auth->realm = Tools::getHttpHost(true);
            $this->auth->returnUrl = $this->getRedirectUrl();
						
            try {
                $url = $this->auth->authUrl();
                  Tools::redirect($url);
            }
            catch (Exception $e) {
                $this->errors[] = $e->getCode().$e->getMessage();
            }
	}
				
	return false;
        
    }
    
}