<?php

return array(
	'id' => 'WMS_COLLECTION_MANAGEMENT',
	'name' => 'WMS Collection Management API',
	'docs' => 'http://www.oclc.org/developer/services/wms-collection-management-api',
	'auth-type' => 'HMAC Signature',
	'requests' => array(
        'read' => array(
			'method' => 'GET',
			'url' => 'https://circ.sd00.worldcat.org/LHR/%oclc-number',
        ),
        'search' => array(
			'method' => 'GET',
			'url' => 'https://circ.sd00.worldcat.org/LHR/?q=oclc:%oclc-number&inst=%inst&principalID=%principalID&principalIDNS=%principal_IDNS',
			'http-accept' => 'application/atom+xml',
        ),
        'searchbaseurl' => array(
			'method' => 'GET',
			'url' => 'https://circ.sd00.worldcat.org/LHR/',
			'http-accept' => 'application/atom+xml',
        ),
	),
);
?>
