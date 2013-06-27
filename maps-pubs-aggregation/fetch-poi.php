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
$center = new GeoJSONPoint( (float) $_GET['lon'], (float) $_GET['lat'] );

//db.poiConcat.aggregate( { $geoNear: { near: [ -0.153191, 51.53419911 ],
//		distanceField : 'distance', distanceMultiplier: 6371, maxDistance:
//		5000, spherical: true, num: 10, query: { ts: 'amenity=pub' } } } );
$res = $c->aggregate( array(
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
) );
$s = $res['result'];

$rets = format_response( $s, false );

echo json_encode( $rets, JSON_PRETTY_PRINT );
?>
