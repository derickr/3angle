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

$query = array(
	LOC => array(
		'$near' => array(
			'$geometry' => $center->getGeoJSON(),
			'$maxDistance' => 5000,
		),
	),
	TAGS => 'amenity=pub',
);

$s = $c->find( $query, [ 'limit' => 5 ] );
$s->setTypeMap( [ 'root' => 'Array', 'document' => 'Array' ] );

$rets = format_response( $s, false );

echo json_encode( $rets, JSON_PRETTY_PRINT );
?>
