<?php
include '../config.php';
include '../classes.php';
include '../display.php';

ob_start("ob_gzhandler");

ini_set('display_errors', 1);
ini_set('error_reporting', -1);

header('Content-type: text/plain');
$m = new \MongoDB\Driver\Manager( 'mongodb://localhost' );

$segments = array_key_exists('segments', $_GET) ? (int) $_GET['segments'] : 1;
$set = array_key_exists('set', $_GET) ? (string) $_GET['set'] : false;

$polygon = GeoJSONPolygon::createFromBounds(
	min( 90, (float) $_GET['n'] ),
	min( 180, (float) $_GET['e']) ,
	max( -90, (float) $_GET['s'] ),
	max( -180, (float) $_GET['w'] ),
	$segments
);

$query = array(
	LOC => array(
		'$geoWithin' => array(
			'$geometry' => $polygon->getGeoJSON(),
		),
	),
);
if ($set) {
	$query['sets'] = $set;
}
$s = $m->executeQuery( DATABASE . '.flickr', new \MongoDB\Driver\Query( $query, [ 'limit' => 25000 ] ) );
$s->setTypemap( [ 'document' => 'array', 'root' => 'array' ] );

$rets = format_response( $s, false );

echo json_encode( $rets, JSON_PRETTY_PRINT );
?>
