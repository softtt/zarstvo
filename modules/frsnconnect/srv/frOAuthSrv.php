<?php
/**
  * Social Network connect modules
  * frsnconnect 0.15 by froZZen
 */

require_once 'frSNSrvBase.php';

require_once 'lib/OAuthUtil.php';

class OAuthConsumer {
  
    public $key;
    public $secret;

  
    function __construct($key, $secret, $callback_url=NULL) {
    
        $this->key = $key;
        $this->secret = $secret;
        $this->callback_url = $callback_url;
        
    }

    function __toString() {
        
        return "OAuthConsumer[key=$this->key,secret=$this->secret]";
        
    }

}

class OAuthToken {
    
    // access tokens and request tokens
    public $key;
    public $secret;

    function __construct($key, $secret) {
        
        $this->key = $key;
        $this->secret = $secret;
        
    }
  
    function to_string() {
    
        return "oauth_token=" .
            OAuthUtil::urlencode_rfc3986($this->key) .
            "&oauth_token_secret=" .
            OAuthUtil::urlencode_rfc3986($this->secret);
        
    }

    function __toString() {
    
        return $this->to_string();
        
    }
    
}

/**
 * EOAuthService is a base class for all OAuth providers.
 */
abstract class frOAuthSrv extends frSNSrvBase { 

    protected $type = 'OAuth';

    /**
     * @var EOAuthUserIdentity the OAuth library instance.
     */
    private $auth;
	
    /**
     * @var string OAuth2 client id. 
     */
    protected $key;
	
    /**
     * @var string OAuth2 client secret key.
     */
    protected $secret;
	
    /**
     * @var string OAuth scopes. 
     */
    protected $scope = '';
	
    /**
     * @var array Provider options. Must contain the keys: request, authorize, access.
     */
    protected $providerOptions =  array(
        'request' => '',
	'authorize' => '',
	'access' => '',
	);
	
    /**
     * Initialize the component.
     * @param EAuth $component the component instance.
     * @param array $options properties initialization.
     */
    public function init($options = array()) {	
            
        parent::init($options);
		
        $this->key = $options['sn_service_key_id'];
        $this->secret = $options['sn_service_key_secret'];

    }
	
    /**
     * Authenticate the user.
     * @return boolean whether user was successfuly authenticated.
     */
    public function authenticate() {

        if (isset($_REQUEST['oauth_token'])) 
            $oauthToken = $_REQUEST['oauth_token'];
        
        if (isset($_REQUEST['oauth_verifier'])) 
            $oauthVerifier = $_REQUEST['oauth_verifier'];
        

        try {

            if (!isset($oauthToken)) {
                // Create consumer.
                $consumer = new OAuthConsumer($this->key, $this->secret);

                // Set the scope (must match service endpoint).
                $scope = $this->scope;

                // Set the application name as it is displayed on the authorization page.
                $applicationName = Configuration::get('PS_SHOP_NAME');

                // Use the URL of the current page as the callback URL.
                $protocol = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")
                    ? 'https://' : 'http://';
                $server = $_SERVER['HTTP_HOST'];
                $path = $_SERVER["REQUEST_URI"];
                $js = '';
                if (isset($_GET['js']))
                    $js = '&js=true';
                $callbackUrl = $protocol . $server . $path.'&state=snLogin_'.$this->type.$js;
            
//            $this->errors[] = $callbackUrl;

                // Get request token.
                $token = self::GetRequestToken($consumer, $scope,
                    $this->providerOptions['request'], $applicationName, $callbackUrl);
//            $this->errors[] = '(1)'.$token;
//            $this->errors[] = '(1)'.$consumer;

                // Store consumer and token in session.
                $this->setState('OAUTH_CONSUMER_KEY', $consumer->key);
                $this->setState('OAUTH_CONSUMER_SECRET', $consumer->secret);
                $this->setState('OAUTH_TOKEN_KEY', $token->key);
                $this->setState('OAUTH_TOKEN_SECRET', $token->secret);

                // Get authorization URL.
                $url = $this->providerOptions['authorize'] . "?oauth_token=" . $token->key;
                header('Location: '.$url, true, 302);
                exit();
            } 
            else if (!$this->restoreAccessToken()) {
                // Retrieve consumer and token from session.
                $consumer = new OAuthConsumer($this->getState('OAUTH_CONSUMER_KEY'), $this->getState('OAUTH_CONSUMER_SECRET'));
                $token = new OAuthToken($this->getState('OAUTH_TOKEN_KEY'), $this->getState('OAUTH_TOKEN_SECRET'));

//            $this->errors[] = '(2)'.$token;
//            $this->errors[] = '(2)'.$consumer;

                // Set authorized token.
                $token->key = $oauthToken;
 
                // Upgrade to access token.
                $token = self::GetAccessTokenFromSrv($consumer, $token, $oauthVerifier,
                    $this->providerOptions['access']);
            
//            $this->errors[] = '(3token)'.$token;

                $this->setState('ACCESS_TOKEN_KEY', $token->key);
                $this->setState('ACCESS_TOKEN_SECRET', $token->secret);
            
                $this->setState('OAUTH_TOKEN_KEY', null);
                $this->setState('OAUTH_TOKEN_SECRET', null);
            
                $this->authenticated = true;
//            $this->errors[] = ($this->authenticated) ? 'true' : 'false';
            }

        } catch (Exception $e) {
            $this->errors[]=$e->getMessage();
        }

        return $this->getIsAuthenticated();

    }
        
    protected function restoreAccessToken() {
    
        if ($this->hasState('ACCESS_TOKEN_KEY')
            && $this->hasState('ACCESS_TOKEN_SECRET')
            && $this->hasState('OAUTH_CONSUMER_SECRET')
            && $this->hasState('OAUTH_CONSUMER_SECRET')
            && $this->getState('expires', 0) > time()) 
        {
            $this->authenticated = true;
            return true;
	}
	else {
            $this->authenticated = false;
            return false;
	}
        
    }   
    
   /**
   * Using the provided consumer and authorized request token, a request is
   * made to the endpoint to generate an OAuth access token.
   * @param OAuthConsumer $consumer the consumer
   * @param OAuthToken $token the authorized request token
   * @param string $verifier the OAuth verifier code returned with the callback
   * @param string $endpoint the OAuth endpoint to make the request against
   * @return OAuthToken an access token
   * @see http://code.google.com/apis/accounts/docs/OAuth_ref.html#AccessToken
   */
    public static function GetAccessTokenFromSrv(OAuthConsumer $consumer,
        OAuthToken $token, $verifier, $endpoint) {

        // Set parameters.
        $params = array();
        $params['oauth_verifier'] = $verifier;

        // Create and sign request.
        $defaults = array("oauth_version" => '1.0',
            "oauth_nonce" => md5(microtime().mt_rand()),
            "oauth_timestamp" => time(),
            "oauth_consumer_key" => $consumer->key
            );
    
        if ($token)
            $defaults['oauth_token'] = $token->key;

        $params = array_merge($defaults, $params);

        $params['oauth_signature_method'] = 'HMAC-SHA1';

        $base_string = implode('&', 
            OAuthUtil::urlencode_rfc3986(array(
                strtoupper('GET'),
                self::get_normalized_http_url($endpoint),
                OAuthUtil::build_http_query($params)
                )
            ));

        $key_parts = array(
            $consumer->secret,
            ($token) ? $token->secret : ""
            );

        $key_parts = OAuthUtil::urlencode_rfc3986($key_parts);
        $key = implode('&', $key_parts);

        $signature = base64_encode(hash_hmac('sha1', $base_string, $key, true));

        $params['oauth_signature'] = $signature;
 
        $post_data = OAuthUtil::build_http_query($params);
        $out = self::get_normalized_http_url($endpoint);
        if ($post_data) 
            $out .= '?'.$post_data;
    
        $url = $out;

        // Get token.
        return self::GetTokenFromUrl($url);
        
  }
  
  /**
   * Using the consumer and scope provided, a request is made to the endpoint
   * to generate an OAuth request token.
   * @param OAuthConsumer $consumer the consumer
   * @param string $scope the scope of the application to authorize
   * @param string $endpoint the OAuth endpoint to make the request against
   * @param string $applicationName optional name of the application to display
   *     on the authorization redirect page
   * @param string $callbackUrl optional callback URL
   * @return OAuthToken a request token
   * @see http://code.google.com/apis/accounts/docs/OAuth_ref.html#RequestToken
   */
    public static function GetRequestToken(OAuthConsumer $consumer, $scope,
        $endpoint, $applicationName, $callbackUrl) {

        // Set parameters.
        $params = array();
        $params['scope'] = $scope;
        if (isset($applicationName)) 
            $params['xoauth_displayname'] = $applicationName;
    
        if (isset($callbackUrl)) 
            $params['oauth_callback'] = $callbackUrl;
        

        // Create and sign request.
        $defaults = array("oauth_version" => '1.0',
                      "oauth_nonce" => md5(microtime().mt_rand()),
                      "oauth_timestamp" => time(),
                      "oauth_consumer_key" => $consumer->key);

        $params = array_merge($defaults, $params);

        $params['oauth_signature_method'] = 'HMAC-SHA1';

        $base_string = implode('&', 
            OAuthUtil::urlencode_rfc3986(array(
                strtoupper('GET'),
                self::get_normalized_http_url($endpoint),
                OAuthUtil::build_http_query($params)
                )));

        $key_parts = array(
            $consumer->secret,
            ""
            );

        $key_parts = OAuthUtil::urlencode_rfc3986($key_parts);
        $key = implode('&', $key_parts);

        $signature = base64_encode(hash_hmac('sha1', $base_string, $key, true));

        $params['oauth_signature'] = $signature;

        $post_data = OAuthUtil::build_http_query($params);
        $out = self::get_normalized_http_url($endpoint);
        if ($post_data) 
            $out .= '?'.$post_data;
        $url = $out;
  
        // Get token.
        return self::GetTokenFromUrl($url);
//        return $url;  
    
    }

    private static function GetTokenFromUrl($url) {
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        $headers = curl_getinfo($ch);
        curl_close($ch);

        if ($headers['http_code'] != 200) 
            $this->errors[] = $response;
    
        $values = array();
        parse_str($response, $values);
    
        return new OAuthToken($values['oauth_token'],
            $values['oauth_token_secret']);

    } 

    /**
    * parses the url and rebuilds it to be
    * scheme://host/path
    */
    public static function get_normalized_http_url($http_url) {
    
        $parts = parse_url($http_url);

        $port = @$parts['port'];
        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $path = @$parts['path'];

        $port or $port = ($scheme == 'https') ? '443' : '80';

        if (($scheme == 'https' && $port != '443')
            || ($scheme == 'http' && $port != '80')) 
        {
            $host = "$host:$port";
        }
    
        return "$scheme://$host$path";
        
    }  
  
    /**
     * Returns the OAuth consumer.
     * @return object the consumer.
     */
    protected function getConsumer() {

        return new OAuthConsumer($this->getState('OAUTH_CONSUMER_KEY'), $this->getState('OAUTH_CONSUMER_SECRET'));
                
    }
	
    /**
     * Returns the OAuth access token.
     * @return string the token.
     */
    protected function getAccessToken() {

        return new OAuthToken($this->getState('ACCESS_TOKEN_KEY'), $this->getState('ACCESS_TOKEN_SECRET'));
	
    }
	
    /**
     * Initializes a new session and return a cURL handle.
     * @param string $url url to request.
     * @param array $options HTTP request options. Keys: query, data, referer.
     * @param boolean $parseJson Whether to parse response in json format.
     * @return cURL handle.
     */
    protected function initRequest($url, $options = array()) {

        $ch = parent::initRequest($url, $options);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
	
        return $ch;
        
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
					
        $consumer = $this->getConsumer();
        $token = $this->getAccessToken();

	$query = null;
	if (isset($options['query'])) {
            $query = $options['query'];
            unset($options['query']);
	}
		
        $defaults = array("oauth_version" => '1.0',
                      "oauth_nonce" => md5(microtime().mt_rand()),
                      "oauth_timestamp" => time(),
                      "oauth_consumer_key" => $consumer->key);
        if ($token)
            $defaults['oauth_token'] = $token->key;

        if (!is_null($query))
            $params = array_merge($defaults, $query);
        else 
            $params = $defaults;
      
        $params['oauth_signature_method'] = 'HMAC-SHA1';

        $base_string = implode('&', 
            OAuthUtil::urlencode_rfc3986(array(
                strtoupper(isset($options['data']) ? 'POST' : 'GET'),
                self::get_normalized_http_url($url),
                OAuthUtil::build_http_query($params)
                )));

        $key_parts = array(
            $consumer->secret,
            ($token) ? $token->secret : ""
            );

        $key_parts = OAuthUtil::urlencode_rfc3986($key_parts);
        $key = implode('&', $key_parts);

        $signature = base64_encode(hash_hmac('sha1', $base_string, $key, true));

        $params['oauth_signature'] = $signature;

        $post_data = OAuthUtil::build_http_query($params);
        $out = self::get_normalized_http_url($url);
        if ($post_data) 
            $out .= '?'.$post_data;
    
        $url = $out;
                
	return $this->makeRequest($url, $options, $parseJson);
        
    }
    
}