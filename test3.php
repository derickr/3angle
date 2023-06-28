<?php
include 'classes.php';

// defines
define( 'TYPE', 'ty' );
$center = new GeoJSONPoint( 0.99, 50.097 );

// connect and select database 'demo' and collection 'timezone'
$m = new MongoClient;
$d = $m->demo;
$tzc = $d->selectCollection( 'timezone' );

$s = $tzc->aggregate(
	[ '$geoNear' => [
		'near' => $center->getGeoJSON(),
		'distanceField' => 'd',
		'distanceMultiplier' => 1,
		'maxDistance' => 22000,
		'spherical' => true,
		'query' => [
			TYPE => [ '$gte' => 2 ],
		],
		'limit' => 4,
	] ],
	[ '$sort' => [ 'd' => 1 ] ],
	[ '$limit' => 1 ]
);

var_dump( $s['result'][0] );
?>
