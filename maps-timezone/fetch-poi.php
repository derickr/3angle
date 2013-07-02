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

$rets = array();

$tzc = $d->selectCollection( 'timezone' );
$query = array(
	LOC => array(
		'$geoIntersects' => array(
			'$geometry' => $center->getGeoJSON(),
		),
	),
);

// this finds the first TZID
$s = $tzc->findOne( $query );

if (!$s)
{
	$query = array(
		LOC => array(
			'$geoNear' => array(
				'$geometry' => $center->getGeoJSON(),
			),
			'$maxDistance' => 22000,
		),
		TYPE => 2,
	);

	// this finds the first TZID
	$s = $tzc->aggregate(
			array( '$geoNear' => array(
				'near' => $center->getGeoJSON(),
				'distanceField' => 'd',
				'distanceMultiplier' => 1,
				'maxDistance' => 22000,
				'spherical' => true,
				'query' => array(
					TYPE => array( '$gte' => 2 ),
				),
				'limit' => 4,
			) ),
			array( '$sort' => array( 'd' => 1 ) ),
			array( '$limit' => 1 )
	);
	if (isset( $s['result'][0] ) )
	{
		$s = $s['result'][0];
	}
	else
	{
		$s = false;
	}
}

$tag = false;

if ( isset( $s[TAGS] ) )
{
	$tag = $s[TAGS][0];
}

if ( $tag )
{
	$query = array(
		TAGS => $tag
	);
	$s = $tzc->find( $query );
}
else
{
	$ew1 = -7.5 + 15 * ceil(($center->p[0] - 7.5) / 15);
	$ew2 = 7.5 + 15 * ceil(($center->p[0] - 7.5) / 15);

	$offset = (int) (($center->p[0] - 7.5) / 15);
	$sign = $offset < 0 ? '-' : '+';

	$s[0] = array(
		'_id' => 'tz' . $offset,
		'l' => array(
			'type' => 'Polygon',
			'coordinates' => array( array( 
				array( $ew1,  85 ),
				array( $ew1, -85 ),
				array( $ew2, -85 ),
				array( $ew2,  85 ),
				array( $ew1,  85 ),
			) ),
		),
		'ts' => array(
			sprintf( "TZID=Etc/GMT%s%d", $sign, abs($offset) )
		),
	);
}

$r = array();

foreach ( $s as $record )
{
	$tz = new DateTimeZone( substr( $record['ts'][0], 5 ) );
	$d = new DateTime();
	$d->setTimezone( $tz );
	$record['ts'][] = 'Time=' . $d->format('Y-m-d H:i:s T (O)');

	$r[] = $record;
}

$rets = format_response( $r, false );

echo json_encode( $rets, JSON_PRETTY_PRINT );
