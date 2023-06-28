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
		),
//		'$maxDistance' => 250000
	),
//	TAGS => 'amenity=pub',
	TAGS => array(
		'$in' => array(
			new \MongoDB\BSON\Regex( "/^amenity=/" ),
			new \MongoDB\BSON\Regex( "/^shop=/" ),
			new \MongoDB\BSON\Regex( "/^tourism=/" ),
		)
	)
);
$s = $c->find( $query, [ 'limit' => 50 ] );

$rets = format_response( $s, true );
ini_set('html_errors', 0 );
/*
foreach( $rets as $id => $ret )
{
	$rets[$id]['properties']['popupContent'] .= "<br/>Score: {$rets[$id]['properties']['score']}";
}
*/
echo json_encode( $rets, JSON_PRETTY_PRINT );
?>
