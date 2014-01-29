<?php
include '../config.php';
include '../classes.php';
include '../display.php';
include '../tags.php';

ob_start("ob_gzhandler");

ini_set('display_errors', 1);
ini_set('html_errors', 0);
ini_set('error_reporting', -1);

header('Content-type: text/plain');
$m = new MongoClient( 'mongodb://localhost' );
$d = $m->selectDb( DATABASE );
$c = $d->selectCollection( COLLECTION );
$center = new GeoJSONPoint( (float) $_GET['lon'], (float) $_GET['lat'] );

$res = $c->aggregate( array(
	array( '$geoNear' => array(
		'near' => $center->getGeoJson(),
		'distanceField' => 'distance',
		'distanceMultiplier' => 1,
		'maxDistance' => 1000,
		'spherical' => true,
		'query' => array( TAGS => 'amenity=post_box' ),
		'limit' => 40,
	) ),
	array( '$sort' => array( 'distance' => 1 ) ),
) );

$s = array();
if ( array_key_exists( 'result', $res ) )
{
	$s = $res['result'];
}

$skipFirstNotFound = false;

foreach( $s as $key => $r )
{
	$tags = Functions::split_tags( $r[TAGS] );

	/* If there is a ref, use it, otherwise set ??? */
	if ( array_key_exists( 'ref', $tags ) )
	{
		$pbref = $tags['ref'];
	}
	else
	{
		$pbref = '???';
	}

	$s[$key]['score'] = 0;
	if ( array_key_exists( 'meta', $r ) )
	{
		if ( array_key_exists( 'visited', $r['meta'] ) )
		{
			$s[$key]['score'] = 50;
		}
		if ( array_key_exists( 'finished', $r['meta'] ) )
		{
			$s[$key]['score'] = 100;
		}
	}

	$s[$key][TAGS][] = "name={$pbref}";

	if ( $s[$key]['score'] == 0 && $skipFirstNotFound )
	{
		$skipFirstNotFound = false;
		unset( $s[$key] );
	}

	unset( $s[$key]['distance'] );
}

$rets = format_response( $s, false );

echo json_encode( $rets, JSON_PRETTY_PRINT );
?>
