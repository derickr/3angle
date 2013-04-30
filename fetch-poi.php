<?php
include 'config.php';
include 'classes.php';

header('Content-type: text/plain');
$m = new MongoClient( 'mongodb://localhost' );
$d = $m->selectDb( DATABASE );
$c = $d->selectCollection( COLLECTION );
$center = new GeoJSONPoint( (float) $_GET['lon'], (float) $_GET['lat'] );

$rets = array();

/*
$query = array(
	TAGS => array(
		'$in' => array(
			new MongoRegex( "/^amenity=/" ),
			new MongoRegex( "/^shop=/" ),
			new MongoRegex( "/^tourism=/" ),
		)
	)
);
$query = array( TAGS => 'amenity=pub' );
*/
/* Everything *
$query = array(
	LOC => array(
		'$near' => array(
			'$geometry' => $center->getGeoJSON(),
			'$maxDistance' => 500
		),
	),
	TAGS => array( '$exists' => true ),
);
$s = $c->find( $query )->limit( 400 );
*/

/* FIVE CLOSEST PUBS *
$query = array(
	LOC => array(
		'$near' => array(
			'$geometry' => $center->getGeoJSON(),
			'$maxDistance' => 500
		),
	),
	TAGS => 'amenity=pub',
);
$s = $c->find( $query )->limit( 50 );
/*/

/* HYDEPARK and CAFES *
$query = array( '_id' => array( '$in' => array( "n1696895511", "n1696895509", "n130210673", "n1696895513", "w157472706", "w19851241" ) ) );
$s = $c->find( $query )->limit( 10 );
*/

/* BUILDING and INTERSECTS *
$building = $c->findOne( array( '_id' => "w4376720" ) );
$query = array( 
	LOC => array( '$geoIntersects' => array( '$geometry' => $building['l'] ) ),
	TAGS => array( '$exists' => true ),
);
$s = $c->find( $query )->sort( array( 'l.type' => -1 ) );
/*/

/* CLOSESTS with DISTANCE (aggregation) */

//db.poiConcat.aggregate( { $geoNear: { near: [ -0.153191, 51.53419911 ],
//		distanceField : 'distance', distanceMultiplier: 6371, maxDistance:
//		5000, spherical: true, num: 10, query: { ts: 'amenity=pub' } } } );
$res = $c->aggregate( array(
	'$geoNear' => array(
		'near' => $center->p,
		'distanceField' => 'distance',
		'distanceMultiplier' => 6371000,
		'maxDistance' => 500 / 6371000,
		'spherical' => true,
		'query' => array( '$or' => array( array( TAGS => 'amenity=pub' ), array( TAGS => 'amenity=bar' ) ) ),
	)
) );

$s = $res['result'];

/*
foreach( $s['results'] as $res)
{
	$o = $res['obj'];
*/
foreach( $s as $o )
{
	$ret = array(
		'type' => 'Feature',
		'properties' => array( 'popupContent' => '', 'changed' => false ),
	);
	if ( isset( $o['possible'] ) )
	{
		$ret['properties']['changed'] = true;
	}
	if ( isset( $o[TAGS] ) ) {
		$name = $content = '';
		$classes = array();
		foreach ( $o[TAGS] as $tagName => $value ) {
			list( $tagName, $value ) = explode( '=', $value );
			if ( $tagName == 'name' ) {
				$name = $value; 
			} else {
				$content .= "<br/>{$tagName}: {$value}\n";
			}
			if ( in_array( $tagName, array( 'amenity', 'leisure' ) ) )
			{
				$classes[] = preg_replace( '/[^a-z0-9]/', '', $tagName . $value );
			}
		}
		$content .= "<br/><form action='checkin.php' method='post'><input type='hidden' name='object' value='{$o['_id']}'/><input type='submit' value='check in'/></form>";
		$ret['properties']['name'] = $name;
		if ( isset( $o['distance'] ) )
		{
			$ret['properties']['name'] .= "<br/>\n(". sprintf('%d m', $o['distance']) . ')';
		}
		$ret['properties']['classes'] = join( ' ', $classes );
		$ret['properties']['popupContent'] = "<b>{$name}</b>" . $content;
	}

	$ret['geometry'] = $o[LOC];

	$rets[] = $ret;
}
echo json_encode( $rets, JSON_PRETTY_PRINT );
