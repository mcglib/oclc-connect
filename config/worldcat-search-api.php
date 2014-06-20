<?php

return array(
	'id' => 'wcapi',
	'name' => 'WorldCat Search API',
	'docs' => 'http://www.oclc.org/developer/services/worldcat-search-api',
	'auth-type' => 'WSKey Lite',
	'requests' => array(
		'opensearch' => array(
			'method' => 'GET',
			'url' => 'http://www.worldcat.org/webservices/catalog/search/worldcat/opensearch',
		),
		'sru' => array(
			'method' => 'GET',
			'url' => 'http://www.worldcat.org/webservices/catalog/search/sru',
		),
		'content' => array(
			'method' => 'GET',
			'url' => 'http://www.worldcat.org/webservices/catalog/content/%oclc-number',
		),
		'isbn' => array(
			'method' => 'GET',
			'url' => 'http://www.worldcat.org/webservices/catalog/content/isbn/%isbn',
		),
		'citations' => array(
			'method' => 'GET',
			'url' => 'http://www.worldcat.org/webservices/catalog/content/citations/%oclc-number',
		),
	),
);