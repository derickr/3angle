<?php
include 'classes.php';

// defines and setup our center point variable
define( 'LOC', 'l' );
$center = new GeoJSONPoint( 5.3009033203125, 53.38824275010831 );

// connect and select database 'demo'
$m = new MongoClient;
$d = $m->demo;

// Select timezone collection
$tzc = $d->selectCollection( 'timezone' );

// Construct geo query for current centre point.
$query = array(
	LOC => array(
		'$geoIntersects' => array(
			'$geometry' => $center->getGeoJson(),
		),
	),
);

// this finds the first TZID
$s = $tzc->findOne( $query );

var_dump( $s );
?>
