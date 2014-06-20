<?php

return array(
	'name' => 'Access Token',
	'id' => '',
	'docs' => 'http://www.oclc.org/developer/platform/explicit-authorization-code',
	'auth-type' => 'HMAC Signature',
	'requests' => array(
		'access-token' => array(
			'method' => 'POST',
			'url' => 'https://authn.sd00.worldcat.org/oauth2/accessToken',
			'args' => array(
				'grant_type' => 'authorization_code',
				'response_type' => 'code',
				'code' => 'The authorization code returned by the authorization server.',
				'authenticatingInstitutionId' => 'The institution that is responsible for authenticating the user.',
				'contextInstitutionId' => 'The institution’s whose data the client is requesting access to.',
				'scope' => 'The services that the client is requesting access to. A webservice id CSV',
				'redirect_uri' => 'The url the authorization server should redirect the user to after login.',
			),
		),
	),
);