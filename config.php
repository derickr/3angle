<?php
define( 'DATABASE', 'demo' );
define( 'COLLECTION', 'poiConcat' );

define( 'TYPE', 'ty' );
define( 'LOC', 'l' );
define( 'TAGS', 'ts' );
define( 'META', 'm' );

$layers = [
	'Flickr' => [
		'directory' => 'maps-flickr',
		'layerName' => 'flickr',
	],
	'Great Circle' => [
		'directory' => 'maps-great-circle',
		'layerName' => 'gc',
	],
	'Timezone' => [
		'directory' => 'maps-timezone',
		'layerName' => 'timezone',
	]
];
?>
