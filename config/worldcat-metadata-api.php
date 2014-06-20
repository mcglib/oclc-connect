<?php

return array(
	'id' => 'WorldCatMetadataAPI',
	'name' => 'WorldCat Metadata API',
	'docs' => 'http://www.oclc.org/developer/services/worldcat-metadata-api',
	'auth-type' => 'HMAC Signature',
	'requests' => array(
		'bibliographic-records-read' => array(
			'method' => 'GET',
			'url' => 'https://worldcat.org/bib/data/%oclc-number',
		),
		'holdings' => array(
			'method' => 'GET',
			'url' => 'https://worldcat.org/ih/data',
		),
		'holding-codes' => array(
			'method' => 'GET',
			'url' => 'https://worldcat.org/bib/holdinglibraries',
		),
		'local-bibliographic-data' => array(
			'method' => 'GET',
			'url' => 'https://worldcat.org/lbd/data',
		),
	),
);