<?php
define( 'DATABASE', 'demo' );
define( 'COLLECTION', 'poiConcat' );

define( 'NOTESCACHECOLLECTION', 'notescache' );

define( 'TYPE', 'ty' );
define( 'LOC', 'l' );
define( 'TAGS', 'ts' );
define( 'META', 'm' );

define( 'DESC_VERSION', 2 );

$layers = [
	'3angle' => [
		'directory' => 'maps-3angle',
		'layerName' => 'threeangle',
	],
	'5 Pubs' => [
		'directory' => 'maps-5pubs',
		'layerName' => 'fivepubs',
	],
	'Events' => [
		'directory' => 'maps-events',
		'layerName' => 'events',
	],
	'Flickr' => [
		'directory' => 'maps-flickr',
		'layerName' => 'flickr',
	],
	'Foursquare' => [
		'directory' => 'maps-4sq',
		'layerName' => 'foursquare',
	],
	'Great Circle Box' => [
		'directory' => 'maps-great-circle',
		'layerName' => 'gc',
	],
	'Great Circle Line' => [
		'directory' => 'maps-great-circle-line',
		'layerName' => 'gcl',
	],
	'Great Circle Radius' => [
		'directory' => 'maps-great-circle-radius',
		'layerName' => 'gcr',
	],
	'Nearest Postbox' => [
		'directory' => 'maps-postbox',
		'layerName' => 'postbox',
	],
	'OSM Notes' => [
		'directory' => 'maps-osm-notes',
		'layerName' => 'osmnotes',
	],
	'Postboxes' => [
		'directory' => 'maps-postboxes',
		'layerName' => 'postboxes',
	],
	'Real Cider/Real Ale' => [
		'directory' => 'maps-pubs-aggregation',
		'layerName' => 'pubsaggr',
	],
	'Timezone' => [
		'directory' => 'maps-timezone',
		'layerName' => 'timezone',
	],
	'Timezone (All)' => [
		'directory' => 'maps-all-zones',
		'layerName' => 'timezones',
	],
];
?>
