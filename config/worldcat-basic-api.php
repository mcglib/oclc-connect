<?php

return array(
	'id' => 'wcapi',
	'name' => 'WorldCat Basic API',
	'docs' => 'http://www.oclc.org/developer/services/worldcat-basic-api',
	'auth-type' => '',
	'requests' => array(
		'opensearch' => array(
			'method' => 'GET',
			'url' => 'http://www.worldcat.org/webservices/catalog/search/worldcat/opensearch',
		),
	),
);