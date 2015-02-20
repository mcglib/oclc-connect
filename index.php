<?php

/**
 * OCLC's web services documentation can be found here
 *   http://www.oclc.org/developer/develop/web-services.en.html
 *
 * So, why use this library?
 *   There are 20+ APIs, and 3 authentication methods of varying complexity.
 *   Each API varies on how requests are made.
 *   Also, the authentication method that should be applied, varies from API to API.
 *   This library abstracts the authentication methods, and provides a consistent interface for the APIs.
 */

/**
 * Include the OCLCWebServices library.
 */
include('classes/McGill/OCLCWebServices.php');

// Start a session. We will need to store/retrieve an access token across requests.
session_start();

/**
 * Copy config.php.sample to config.php.
 * Adjust config.php such that all details of the WSKey are captured in this file.
 * Your WSKey is configured here: https://platform.worldcat.org/wskey/
 * If you do not have a WSKey, create an account here: http://www.oclc.org/user/create-account.en.html
 */
$config = include('config.php');

// Instantiate an OCLCWebServices object.
$oclc = new McGill\OCLCWebServices($config, 'et-sandbox3');

// There are three possible outcomes at this point...
if (@$_SESSION['accessToken']){
	/**
	 * We've successfully logged in earlier and therefore have an access token.
	 * Let's apply the access token to this OCLCWebServices instance and move on to using APIs.
	 */
	$oclc->setAccessToken($_SESSION['accessToken']);
}
else if (@$_REQUEST['auth_code']){
	/**
	 * This occurs during the post-login phase.
	 * Once OCLC supplies an auth_code, we need to fetch an access token.
	 * Let's apply the access token to this OCLCWebServices instance and move on to using APIs.
	 */
	$_SESSION['accessToken'] = $oclc->fetchAccessTokenByAuthCode($_REQUEST['auth_code']);
	$oclc->setAccessToken($_SESSION['accessToken']);
}
else {
	/**
	 * This occurs during the pre-login phase.
	 * It redirects the browser to an OCLC login page and dies.
	 * Once the user submits their login information, OCLC will perform an HTTP GET request
	 * to the Redirect URI configured for you WSKey. The Redirect URI should point to this script.
	 */
	$oclc->login();
}

/**
 * We are all set and ready to perform a requst
 * Supply the request method with an API, the web service, and an associative array of arguments.
 *   These values are simply associative array keys found in config.php.
 * For documentation on each web service, visit: http://www.oclc.org/developer/develop/web-services.en.html
 */
$response = $oclc->request('worldcat-search-api', 'opensearch', array(
	'q' => 'my book',
));

// Do something with the response.
var_dump($response);

/**
 * Need to access a config value?
 *   You may want to use %wskey to dynamically refer to the choosen WSKey config.
 */
echo $oclc->config('wskeys.%wskey.api-key');
