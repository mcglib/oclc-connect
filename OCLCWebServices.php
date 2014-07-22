<?php namespace McGill;
/*
There are nearly 20 different OCLC Web Services, which vary greatly in usage. As such, OCLC Web Services are configuration heavy. This client library adopts the strategy of first choosing a configuration and then issuing a request. The request will behave differently, depending on the configuration chosen. This keeps the code base lean, and restricts changes just to configuration files. We will pursue it and see where it goes. The central idea is this:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

/**
 * oclc/config.php returns a gigantic array tree of configuration data.
 * This array configures the OCLCWebServices::request() method.
 * /
$config = include('oclc/config.php');

/**
 * Create an instance of OCLCWebServices.
 * /
$oclc = new McGill\OCLCWebServices($config, 'MY-WSKEY');

/**
 * The login process begins with this call. This will redirect the browser to an
 * OCLC login page and cause the execution to die.
 * /
$oclc->login();

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

/**
 * Once the browser is redirected back to our server, we need to, again, re-instantiate
 * our OCLCWebServices object, fetch the access token, and assign it to a session.
 * /
$config = include('oclc/config.php');

$oclc = new McGill\OCLCWebServices($config, 'MY-WSKEY');

$_SESSION['accessToken'] = $oclc->fetchAccessTokenByAuthCode($authCode);

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

/**
 * Before every request, retrieve the access token from the session and assign it 
 * to the OCLCWebServices instance.
 * /
$oclc->setAccessToken($_SESSION['accessToken']);

/**
 * And now we're ready to query. 'MY-SERVICE' and 'MY-REQUEST' are array 
 * keys in $config that provide configuration details to OCLCWebServices::request().
 * The full config path would look like this:
 * $config['wskeys']['MY-WSKEY']['services']['MY-SERVICE']['requests']['MY-REQUEST']
 *
 * The arguments to use can be determined by OCLC documentation.
 * /
$result = $oclc->request('MY-SERVICE', 'MY-REQUEST', array(
    'arg1' => 'val1',
    'arg2' => 'val2',
    //...
    'argN' => 'valN',
));
var_dump($result);

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

/**
 * It may be necessary to refer to config values. This can easily be done by 
 * using the OCLCWebServices::config() method, passing it a dot-separated 
 * array-keys string. For instance, this:
 * /
echo $oclc->config('wskeys.MY-WSKEY.services.MY-SERVICE.requests.MY-REQUEST');

/**
 * Is the same as doing this:
 * /
echo $config['wskeys']['MY-WSKEY']['services']['MY-SERVICE']['requests']['MY-REQUEST'];

/**
 * For instance, if you need the WSKey value, you could do this:
 * /
echo $oclc->config('wskeys.MY-WSKEY.api-key');

/**
 * It is recommended to use %wskey in place of MY-WSKEY. This keeps the wskey dynamic:
 * /
echo $oclc->config('wskeys.%wskey.api-key');
*/

class OCLCWebServices {
	/**
	 * The entire OCLC webservices configuration.
	 *
	 * @var ArrayTreeValueRetriever
	 */
	protected $config;

	/**
	 * The WSKey that will be used.
	 *
	 * @var string
	 */
	protected $wskey;
	
	/**
	 * The access token JSON, converted to stdClass.
	 *
	 * @var stdClass
	 */
	protected $accessToken;

	/**
	 * The log of all HTTP requests.
	 *
	 * @var array
	 */
	protected $log;

	/**
	 * Create an OCLCWebServices instance.
	 *
	 * @return void
	 */
	public function __construct(array $config_P, $wskey_P){
		$this->config = new ArrayTreeValueRetriever($config_P);
		$this->wskey = $wskey_P;
		$this->accessToken = null;
		$this->log = array();
		
		$this->config->setReferences(array(
			'%wskey' => $this->wskey,
		));
	}

	/**
	 * See documentation for ArrayTreeValueRetriever::get().
	 * 
	 * @param string $key_P
	 * @return mixed
	 */
	public function config($key_P = null){
		return $this->config->get($key_P);
	}
	
	/**
	 * Perform a query on an OCLC webservice.
	 * 
	 * @param string $service_P
	 * @param string $request_P
	 * @param array $args_P Accepts path and query arguments.
	 * @param bool $isRedirect_P N.B. If true, execution dies after this call.
	 * @return string
	 */
	public function request($service_P, $request_P, array $args_P, $isRedirect_P = false){
		$requestURL = $this->config("wskeys.{$this->wskey}.services.{$service_P}.requests.{$request_P}.url");
		
		//The $args_P array makes no distinction between path arguments, and query arguments. We need to 
		//separate them and perform substitutions on path arguments.
		$pathArgs = array();
		$queryArgs = array();
		foreach ($args_P as $key => $value){
			$isPathArg = strpos($requestURL, '%' . $key) !== false;
			if ($isPathArg){
				$pathArgs['%' . $key] = $value;
			} else {
				$queryArgs[$key] = $value;
			}
		}
		$requestURL = str_replace(array_keys($pathArgs), array_values($pathArgs), $requestURL);

		$headers = array();
		switch ($this->config("wskeys.{$this->wskey}.services.{$service_P}.auth-type")){
			case 'WSKey Lite':
				$headers[] = 'wskey: ' . $this->config("wskeys.{$this->wskey}.api-key");
				break;
			case 'HMAC Signature':
				$hmac = $this->getHMACSignature(
					$this->config("wskeys.{$this->wskey}.services.{$service_P}.requests.{$request_P}.method"),
					$requestURL,
					$args_P,
					'',
					$this->config("wskeys.{$this->wskey}.api-key"),
					$this->config("wskeys.{$this->wskey}.secret")
				);
				$headers[] = "Authorization: http://www.worldcat.org/wskey/v2/hmac/v1 clientId=\"{$hmac->clientId}\", timestamp=\"{$hmac->timestamp}\", nonce=\"{$hmac->nonce}\", signature=\"{$hmac->signature}\"" . ($this->accessToken ? ", principalID=\"{$this->accessToken->principalID}\", principalIDNS=\"{$this->accessToken->principalIDNS}\"" : '');
				break;
			case 'Old HMAC Signature':
				$hmac = $this->getHMACSignature(
					$this->config("wskeys.{$this->wskey}.services.{$service_P}.requests.{$request_P}.method"),
					$requestURL,
					$args_P,
					'',
					$this->config("wskeys.{$this->wskey}.api-key"),
					$this->config("wskeys.{$this->wskey}.secret")
				);
				$headers[] = "Authorization: http://www.worldcat.org/wskey/v2/hmac/v1 clientId=\"{$hmac->clientId}\", timestamp=\"{$hmac->timestamp}\", nonce=\"{$hmac->nonce}\", signature=\"{$hmac->signature}\"";
				break;
			case 'Access Tokens':
				$headers[] = 'Authorization: ' . $this->accessToken->token_type . ' ' . $this->accessToken->access_token;
				break;
		}
		# -ET added support for optional HTTP Accept headers 2014-07-04
		if(array_key_exists('http-accept',$this->config("wskeys.{$this->wskey}.services.{$service_P}.requests.{$request_P}"))) {
			$headers[] = 'Accept: ' . $this->config("wskeys.{$this->wskey}.services.{$service_P}.requests.{$request_P}.http-accept");
		}
		# -ET added a User-Agent header 2014-07-04
		$headers[] = "User-Agent: McGill University Digital Initiatives OCLC Web Authentication Services Client 0.1";

		if ($isRedirect_P){
			header('location: ' . $requestURL . '?' . http_build_query($queryArgs));
			die();
		}

		return $this->HTTPRequest(
			$this->config("wskeys.{$this->wskey}.services.{$service_P}.requests.{$request_P}.method"),
			$requestURL,
			$queryArgs,
			$headers
		);
	}

	/**
	 * Generates an HMAC signature.
	 * 
	 * @param string $method The HTTP request method (GET | POST).
	 * @param string $url_P The WSKeyV2-protected URL to access, including get arguments.
	 * @param array $args_P The arguments passed with the request.
	 * @param string $bodyHash_P The hash of the body of the request ($bodyHash can just be set to an empty string if you don't want to use it; it is not required)
	 * @param string $wskey_P The web service key.
	 * @param string $secret_P The secret key.
	 * @return stdClass
	 */
	protected function getHMACSignature($method_P, $url_P, array $args_P, $bodyHash_P, $wskey_P, $secret_P){
		$url_P = 'GET' == $method_P ? $url_P . '?' . http_build_query($args_P) : $url_P;
					
		$SUB = array('+' => '%20', '*' => '%2A', '%7E' => '~');

		$queryString = parse_url($url_P, PHP_URL_QUERY);
		$argPairs = $queryString ? explode('&', $queryString) : array();

		$result = array(
			'clientId' => $wskey_P,
			'timestamp' => time(),
			'nonce' => sprintf("%08x", mt_rand(0, 0x7fffffff)),
			'bodyHash' => $bodyHash_P,
			'method' => strtoupper($method_P),
			'host' => 'www.oclc.org',
			'port' => 443,
			'path' => '/wskey',
		);

		$request = implode("\n", $result) . "\n";
		foreach ($argPairs as $value) {
			$request .= str_replace(array_keys($SUB), array_values($SUB), $value) . "\n";
		}

		$result = $result + array(
			'request' => $request,
			'signature' => base64_encode(hash_hmac("sha256", $request, $secret_P, true))
		);
		
		return (object)$result;
	}
	
	/**
	 * Performs an HTTP request.
	 * 
	 * @param string $method_P Currently GET or POST only.
	 * @param string $url_P
	 * @param array $data_P
	 * @param array $headers_P
	 * @return string
	 */
	protected function HTTPRequest($method_P, $url_P, array $data_P, array $headers_P){
		$ch = curl_init();
		if ('POST' == $method_P){
			curl_setopt($ch, CURLOPT_URL, $url_P);
			curl_setopt($ch, CURLOPT_POST, count($data_P));
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data_P));
		} else {
			curl_setopt($ch, CURLOPT_URL, $url_P . '?' . http_build_query($data_P));
		}
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_P);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSLVERSION, 3);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);

		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);

		curl_close($ch);

		$result = array(
			'errno' => $errno,
			'error' => $error,
			'info' => $info,
			'header' => $response !== false ? substr($response, 0, $info['header_size']) : null,
			'content' => $response !== false ? substr($response, $info['header_size']) : null,
		);

		$this->log[] = array(
			'request' => array(
				'method' => $method_P,
				'url' => $url_P,
				'data' => $data_P,
				'headers' => $headers_P,
			),
			'response' => $result,
		);

		return $result;
	}

	/**
	 * Get the log of all HTTP requests. 
	 * 
	 * @return array
	 */
	public function getLog(){
		return $this->log;
	}

	/**
	 * Get the scope, which are space separated service Ids, for the current WSKey.
	 * 
	 * @return array
	 */
	public function getScope(){
		$result = array();
		foreach ($this->config("wskeys.{$this->wskey}.services") as $serviceKey => $service){
			$serviceId = $this->config("wskeys.{$this->wskey}.services.{$serviceKey}.id");
			
			//Storing serviceIds as array keys to avoid duplicates.
			if ($serviceId) $result[$serviceId] = '';
		}

		// Return space-seperated values as a scope value.
		return implode(' ', array_keys($result));
	}
	
	/**
	 * Redirects the browser to the OCLC login page and execution dies.
	 *
	 * @return void
	 */
	public function login(){
		$this->request('authorize-code', 'authorize-code', array(
			'client_id' => $this->config("wskeys.{$this->wskey}.api-key"),
			'authenticatingInstitutionId' => $this->config("wskeys.{$this->wskey}.registry-id"),
			'contextInstitutionId' => $this->config("wskeys.{$this->wskey}.registry-id"),
			'scope' => $this->getScope(),
			'response_type' => 'code',
			'redirect_uri' => $this->config("wskeys.{$this->wskey}.redirect-url"),
		), true);
	}

	/**
	 * Fetches an access token from the authorization server for a given auth code.
	 *
	 * @param string $authCode_P
	 * @return stdClass
	 */
	public function fetchAccessTokenByAuthCode($authCode_P){
		$result = $this->request('access-token', 'access-token', array(
			'grant_type' => 'authorization_code',
			'code' => $authCode_P,
			'authenticatingInstitutionId' => $this->config("wskeys.{$this->wskey}.registry-id"),
			'contextInstitutionId' => $this->config("wskeys.{$this->wskey}.registry-id"),
			'redirect_uri' => $this->config("wskeys.{$this->wskey}.redirect-url"),
		));
		return json_decode($result['content']);
	}

	/**
	 * Gets the access token.
	 * 
	 * @return stdClass
	 */
	public function getAccessToken(){
		return $this->accessToken;
	}

	/**
	 * Sets the access token.
	 *
	 * @param stdClass $accessToken_P
	 * @return OCLCWebServices
	 */
	public function setAccessToken(\stdClass $accessToken_P){
		$this->accessToken = $accessToken_P;
	}

}