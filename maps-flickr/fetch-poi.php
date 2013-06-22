<?php
include '../config.php';
include '../classes.php';
include '../display.php';

ini_set('display_errors', 1);
ini_set('error_reporting', -1);

header('Content-type: text/plain');
$m = new MongoClient( 'mongodb://localhost' );
$d = $m->selectDb( DATABASE );
$c = $d->selectCollection( COLLECTION );
$polygon = GeoJSONPolygon::createFromBounds( (float) $_GET['n'], (float) $_GET['e'], (float) $_GET['s'], (float) $_GET['w'] );

$c = $d->selectCollection( 'flickr' );
$query = array(
	LOC => array(
		'$geoWithin' => array(
			'$geometry' => $polygon->getGeoJSON(),
		),
	),
);
$s = $c->find( $query )->limit( 8000 );

$rets = format_response( $s, false );

echo json_encode( $rets, JSON_PRETTY_PRINT );
?>
