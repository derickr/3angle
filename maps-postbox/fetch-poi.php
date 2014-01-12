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
	$query = [ LOC => [ '$near' => $r[LOC] ], TAGS => new MongoRegex('/^highway=(trunk|pedestrian|service|primary|secondary|tertiary|residential|unclassified)/' ) ];
	$q = $c->find( $query )->limit(1);
	$road = $q->getNext();
	$roadTags = Functions::split_tags( $road[TAGS] );
	$roadName = array_key_exists( 'name', $roadTags ) ? $roadTags['name'] : "Unknown " . $roadTags['highway'];
	$s[] = $road;

	/* Find all roads that intersect with the $road */
	$q = $c->find( [
		LOC => [ '$geoIntersects' => [ '$geometry' => $road[LOC] ] ],
		TAGS => new MongoRegex('/^highway=(trunk|pedestrian|service|primary|secondary|tertiary|residential|unclassified)/' ),
		'_id' => [ '$ne' => $road['_id'] ],
	] );
	$intersectingWays = array();
	foreach ( $q as $crossRoad )
	{
		$crossTags = Functions::split_tags( $crossRoad[TAGS] );
		if ( !in_array( "name={$roadName}", $crossRoad ) && array_key_exists( 'name', $crossTags ) )
		{
			$intersectingWays[] = $crossRoad['_id'];
		}
	}

	/* Find closest road to the point, only using $intersectingWay roads */
	$res = $c->aggregate( array(
		'$geoNear' => array(
			'near' => $r[LOC],
			'distanceField' => 'distance',
			'distanceMultiplier' => 1,
			'maxDistance' => 5000,
			'spherical' => true,
			'query' => array( '_id' => [ '$in' => $intersectingWays ], TAGS => [ '$ne' => "name={$roadName}" ] ),
			'limit' => 5,
		)
	) );

	$intersectingRoad = false;

	if ( array_key_exists( 'result', $res ) && ( count( $res['result'] ) > 0 ) )
	{
		$intersectingRoad = $res['result'][0];

		$roadTags = Functions::split_tags( $intersectingRoad[TAGS] );
		if ( array_key_exists( 'name', $roadTags ) )
		{
			$intersectRoadName = $roadTags['name'];
		}
		else if ( array_key_exists( 'ref', $roadTags ) )
		{
			$intersectRoadName = $roadTags['ref'];
		}
		else
		{
			$intersectRoadName = "???";
		}
		$s[] = $intersectingRoad;

		$results = count( $res['result'] );
		$secondIntersectingRoad = false;

		if ( $results > 1 )
		{
			$i = 0;
			do {
				$i++;

				/* Second cross road, but we don't really want that if this, and
				 * the first one intersect as well */
				$secondIntersectingRoad = $res['result'][$i];

				$secondIntersectRes = $c->findOne( [
					LOC => [ '$geoIntersects' => [ '$geometry' => $intersectingRoad[LOC] ] ],
					'_id' => $secondIntersectingRoad['_id'],
				] );
			}
			while ( $secondIntersectRes && ($i < $results - 1) );

			if ( array_key_exists( 'name', $roadTags ) )
			{
				$secondIntersectRoadName = $roadTags['name'];
			}
			else if ( array_key_exists( 'ref', $roadTags ) )
			{
				$secondIntersectRoadName = $roadTags['ref'];
			}
			else
			{
				$secondIntersectRoadName = "???";
			}

			if ( $secondIntersectRoadName != $intersectRoadName )
			{
				$secondIntersectingRoad = $res['result'][$i];
				$roadTags = Functions::split_tags( $secondIntersectingRoad[TAGS] );
				$s[] = $secondIntersectingRoad;
			}
			else
			{
				$secondIntersectingRoad = false;
			}
		}
	}

	/* If there is a ref, use it, otherwise set ??? */
	if ( array_key_exists( 'ref', $tags ) )
	{
		$pbref = $tags['ref'];
	}
	else
	{
		$pbref = '???';
	}

	/* Add name tag */
	if ( ! $intersectingRoad )
	{
		$r[TAGS][] = "name={$pbref}<br/>On $roadName";
	}
	else
	{
		if ( $intersectingRoad['distance'] < 20 )
		{
			$r[TAGS][] = "name={$pbref}<br/>On $roadName, on the corner with $intersectRoadName";
		}
		else if ( $intersectingRoad['distance'] > 50 && $secondIntersectingRoad )
		{
			$r[TAGS][] = "name={$pbref}<br/>On $roadName, between $intersectRoadName and $secondIntersectRoadName";
		}
		else
		{
			$r[TAGS][] = "name={$pbref}<br/>On $roadName, near $intersectRoadName";
		}
	}
}

$rets = format_response( $s, false );

echo json_encode( $rets, JSON_PRETTY_PRINT );
?>
