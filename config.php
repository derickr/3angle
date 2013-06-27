<?php
define( 'DATABASE', 'demo' );
define( 'COLLECTION', 'poiConcat' );

define( 'TYPE', 'ty' );
define( 'LOC', 'l' );
define( 'TAGS', 'ts' );
define( 'META', 'm' );

$layers = [
	'5 Pubs' => [
		'directory' => 'maps-5pubs',
		'layerName' => 'fivepubs',
	],
	'Flickr' => [
		'directory' => 'maps-flickr',
		'layerName' => 'flickr',
	],
	'Great Circle' => [
		'directory' => 'maps-great-circle',
		'layerName' => 'gc',
	],
	'Real Cider/Real Ale' => [
		'directory' => 'maps-pubs-aggregation',
		'layerName' => 'pubsaggr',
	],
	'Timezone' => [
		'directory' => 'maps-timezone',
		'layerName' => 'timezone',
	]
];
?>
