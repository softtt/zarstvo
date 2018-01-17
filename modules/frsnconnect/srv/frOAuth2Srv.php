<?php
/**
  * Social Network connect modules
  * frsnconnect 0.15 by froZZen
 */

require_once 'frSNSrvBase.php';

/**
 * frOAuth2Srv is a base class for all OAuth 2.0 providers.
 */
abstract class frOAuth2Srv extends frSNSrvBase { 

    protected $type = 'OAuth2';

    /**
     * @var string OAuth2 client id. 
     */
    protected $client_id;
    
    /**
     * @var string OAuth2 client secret key.
     */
    protected $client_secret;
    
    /**
     * @var string OAuth scopes. 
     */
    protected $scope = '';
    
    /**
     * @var array Provider options. Must contain the keys: authorize, access_token.
     */
    protected $providerOptions = array(
        'authorize' => '',
	'access_token' => '',
	);
    
    /**
     * @var string current OAuth2 access token.
     */
    protected $access_token = '';
	
    public function init($options = array()) {
        parent::init($options);
                    
        $this->client_id = $options['sn_service_key_id'];
        $this->client_secret = $options['sn_service_key_secret'];
    }
    
    /**
     * Authenticate the user.
     * @return boolean whether user was successfuly authenticated.
     */
    public function authenticate() {
        // user denied error
	if (isset($_GET['error']) && $_GET['error'] == 'access_denied') {
            $this->errors[] = 'User denied request.';
            return false;
	}
        
	// Get the access_token and save them to the session.
	if (isset($_GET['code'])) {
            $code = $_GET['code'];
            
//            $this->errors[] = 'code='.$code;
//            $this->errors[] = $_GET['state'];
//            $this->errors[] = $_GET['process'];
            
            $token = $this->getAccessToken($code);
            
            if (isset($token)) {
                $this->saveAccessToken($token);
		$this->authenticated = true;
            }
        }
	// Redirect to the authorization page
	else if (!$this->restoreAccessToken()) {
            // Use the URL of the current page as the callback URL.
            if (isset($_GET['redirect_uri'])) {
                $redirect_uri = $_GET['redirect_uri'];
                if ($this->hasState('redirect_params'))
                    $redirect_uri .= '?'.$this->getState('redirect_params');
            }
            else {
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
            }
            
//            $this->errors[] = $redirect_uri;
            
            $url = $this->getCodeUrl($redirect_uri);
            
//            $this->errors[] = $url;
            
            header('Location: '.$url, true, 302);
            exit();
//            $this->authenticated = true; // test 
        }
		
	return $this->getIsAuthenticated();
    }
        
    /**
     * Returns the url to request to get OAuth2 code.
     */
    protected function getCodeUrl($redirect_uri) {

        return $this->providerOptions['authorize'].'?client_id='.$this->client_id.'&redirect_uri='.urlencode($redirect_uri).'&scope='.$this->scope.'&response_type=code';
        
    }
	
    /**
     * Returns the url to request to get OAuth2 access token.
     */
    protected function getTokenUrl($code) {

        return $this->providerOptions['access_token'].'?client_id='.$this->client_id.'&client_secret='.$this->client_secret.'&code='.$code;
        
    }

    /**
     * Returns the OAuth2 access token.
     */
    protected function getAccessToken($code) {

        $url = $this->getTokenUrl($code);

        return $this->makeRequest($url);
        
    }
	
    /**
     * Save access token to the session.
     */
    protected function saveAccessToken($token) {

        $this->setState('auth_token', $token);
        $this->access_token = $token;
        
    }
	
    /**
     * Restore access token from the session.
     */
    protected function restoreAccessToken() {

        if ($this->hasState('auth_token') && $this->getState('expires', 0) > time()) {
            $this->access_token = $this->getState('auth_token');
            $this->authenticated = true;
            return true;
	}
	else {
            $this->access_token = null;
            $this->authenticated = false;
            return false;
	}
        
    }
	
    /**
     * Returns the protected resource.
     * @param string $url url to request.
     * @param array $options HTTP request options. Keys: query, data, referer.
     * @param boolean $parseJson Whether to parse response in json format.
     * @return string the response. 
     * @see makeRequest
     */
    public function makeSignedRequest($url, $options = array(), $parseJson = true) {

        if (!$this->getIsAuthenticated())
            $this->errors[] = 'Unable to complete the request because the user was not authenticated.';
	$options['query']['access_token'] = $this->access_token;
	$result = $this->makeRequest($url, $options);
	return $result;
        
    }
    
}