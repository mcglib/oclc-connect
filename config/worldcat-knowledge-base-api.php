<?php

return array(
	'id' => 'kbwcapi',
	'name' => 'WorldCat Knowledge Base API',
	'docs' => 'http://www.oclc.org/developer/services/worldcat-knowledge-base-api',
	'auth-type' => 'WSKey Lite',
	'requests' => array(
		'collections' => array(
			'method' => 'GET',
			'url' => 'http://worldcat.org/webservices/kb/rest/collections/search',
		),
		'entries' => array(
			'method' => 'GET',
			'url' => 'http://worldcat.org/webservices/kb/rest/entries/search',
		),
		'export' => array(
			'method' => 'GET',
			'url' => 'http://worldcat.org/webservices/kb/export/:institution_id/:institution_id_openly.jsCate.ioicomm_kbart.txt',
		),
	),
);