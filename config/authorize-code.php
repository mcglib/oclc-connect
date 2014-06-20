<?php

return array(
	'name' => 'Authorize Code',
	'id' => '',
	'docs' => 'http://www.oclc.org/developer/platform/explicit-authorization-code',
	'auth-type' => '',
	'requests' => array(
		'authorize-code' => array(
			'method' => 'GET',
			'url' => 'https://authn.sd00.worldcat.org/oauth2/authorizeCode',
			'args' => array(
				'response_type' => 'code',
				'client_id' => 'WSKey',
				'authenticatingInstitutionId' => 'The institution that is responsible for authenticating the user.',
				'contextInstitutionId' => 'The institution’s whose data the client is requesting access to.',
				'scope' => 'The services that the client is requesting access to. A webservice id CSV',
				'redirect_uri' => 'The url the authorization server should redirect the user to after login.',
			),
		),
	),
);