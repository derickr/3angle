<?php
require '../vendor/autoload.php';
include '../config.php';
include '../classes.php';
include '../display.php';

ob_start("ob_gzhandler");

ini_set('display_errors', 1);
ini_set('error_reporting', -1);

header('Content-type: text/plain');
$m = new \MongoDB\Client( 'mongodb://localhost:27016' );
$d = $m->selectDatabase( DATABASE );
$c = $d->selectCollection( COLLECTION );
$center = new GeoJSONPoint( (float) $_GET['lon'], (float) $_GET['lat'] );

$res = $c->aggregate( [ array(
	'$geoNear' => array(
		'near' => $center->getGeoJson(),
		'distanceField' => 'distance',
		'distanceMultiplier' => 1,
		'maxDistance' => 5000,
		'spherical' => true,
		'query' => array( '$or' => array(
			array( TAGS => 'amenity=pub' ),
			array( TAGS => 'amenity=bar' ),
			array( TAGS => 'amenity=restaurant' )
		) ),
	)
) ] );

$res->setTypeMap( [ 'root' => 'Array', 'document' => 'Array' ] );

$rets = format_response( iterator_to_array( $res ), false );

echo json_encode( $rets, JSON_PRETTY_PRINT );
?>
