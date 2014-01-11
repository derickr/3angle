<?php
include '../config.php';
include '../classes.php';
include '../display.php';

ob_start("ob_gzhandler");

ini_set('display_errors', 1);
ini_set('html_errors', 0);
ini_set('error_reporting', -1);

class Functions
{
	static function split_tag( $tag )
	{
		preg_match( '/^(.*)=(.*)$/', $tag, $match );
		return array( $match[1], $match[2] );
	}

	static function split_tags( array $tags )
	{
		$returnTags = array();

		foreach ( $tags as $tag )
		{
			list( $name, $value ) = self::split_tag( $tag );
			$returnTags[$name] = $value;
		}
		return $returnTags;
	}
}

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
	$tags = Functions::split_tags( $r[TAGS] );

	/* Find closest street */
	$q = $c->find( [ LOC => [ '$near' => $r[LOC] ], TAGS => new MongoRegex('/^highway=(primary|secondary|tertiary|residential|unclassified)/' ) ] )->limit(1);
	$road = $q->getNext();
	$roadTags = Functions::split_tags( $road[TAGS] );
	$roadName = $roadTags['name'];
	$s[] = $road;

	/* Find all roads that intersect with the $road */
	$q = $c->find( [
		LOC => [ '$geoIntersects' => [ '$geometry' => $road[LOC] ] ], 
		TAGS => new MongoRegex('/^highway=(primary|secondary|tertiary|residential|unclassified)/' ),
		'_id' => [ '$ne' => $road['_id'] ],
	] );
	$intersectingWays = array();
	foreach( $q as $crossRoad )
	{
		$intersectingWays[] = $crossRoad['_id'];
	}

	/* Find closest road to the point, only using $intersectingWay roads */
	$q = $c->find( [
		LOC => [ '$near' => $r[LOC] ], 
		'_id' => [ '$in' => $intersectingWays ],
	] )->limit(1);
	$intersectingRoad = $q->getNext();
	$roadTags = Functions::split_tags( $intersectingRoad[TAGS] );
	$intersectRoadName = $roadTags['name'];
	$s[] = $intersectingRoad;

	/* Add name tag */
	if ( array_key_exists( 'ref', $tags ) )
	{
		$r[TAGS][] = "name={$tags['ref']}<br/>On $roadName, near $intersectRoadName";
	}
	else
	{
		$r[TAGS][] = "name=???<br/>On $roadName, near $intersectRoadName";
	}
}

$rets = format_response( $s, false );

echo json_encode( $rets, JSON_PRETTY_PRINT );
?>
