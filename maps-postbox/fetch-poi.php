<?php
include '../config.php';
include '../classes.php';
include '../display.php';
include '../description.php';
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
	'$geoNear' => array(
		'near' => $center->getGeoJson(),
		'distanceField' => 'distance',
		'distanceMultiplier' => 1,
		'maxDistance' => 5000,
		'spherical' => true,
//		'query' => array( TAGS => 'amenity=post_box', 'meta.finished' => [ '$ne' => true ] ),
		'query' => array( TAGS => 'amenity=post_box' ),
		'limit' => 1,
	)
) );

$s = array();
if ( array_key_exists( 'result', $res ) )
{
	$s = $res['result'];
}

foreach( $s as &$r )
{
	if ( !array_key_exists( 'desc', $r ) )
	{
		createDescription( $c, $r );
	}
	else
	{
		$tags = Functions::split_tags($r[TAGS]);
		if ( array_key_exists( 'ref', $tags ) )
		{
			$pbref = $tags['ref'];
		}
		else
		{
			$pbref = '???';
		}
		$r[TAGS][] = "name={$pbref}<br/>{$r['desc']}";
	}

	$r['distance'] = (int) $r['distance'];
	$dir = ( initial_bearing( $center->getGeoJson(), $r[LOC] ) + 360 ) % 360;
	$windlabel = array ('N','NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW','SW', 'WSW', 'W', 'WNW', 'NW', 'NNW');
	$r['dirLabel'] = $windlabel[ fmod( ( ( $dir + 11.25 ) / 22.5), 16 ) ];
	$r['direction'] = $dir;
}

if ( array_key_exists( 'simple', $_GET ) )
{
	unset( $r['ts'], $r['m'], $r['ty'], $r['_id'], $r['direction'] );
	$r['l'] = $r['l']['coordinates'];
	$r['w'] = $label;
	echo json_encode( $r, JSON_PRETTY_PRINT );
}
else
{
	$rets = format_response( $s, false );

	echo json_encode( $rets, JSON_PRETTY_PRINT );
}
?>
