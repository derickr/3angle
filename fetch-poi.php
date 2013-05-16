<?php
include 'config.php';
include 'classes.php';

header('Content-type: text/plain');
$m = new MongoClient( 'mongodb://localhost' );
$d = $m->selectDb( DATABASE );
$c = $d->selectCollection( COLLECTION );
$center = new GeoJSONPoint( (float) $_GET['lon'], (float) $_GET['lat'] );

$rets = array();
$q = false;

if ( isset( $_GET['q'] ) )
{
	$q = preg_replace( '/[^a-z]/', '', $_GET['q'] );
}

switch ( $q )
{
	case 'everything':
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
		break;

	case 'flickr':
		$c = $d->selectCollection( 'flickr' );
		$query = array(
			LOC => array(
				'$near' => array(
					'$geometry' => $center->getGeoJSON(),
				),
			),
			TAGS => 'tag=underground',
		);
		$s = $c->find( $query )->limit( 400 );
		break;

	case 'pubs': /* FIVE CLOSEST PUBS */
		$query = array(
			LOC => array(
				'$near' => array(
					'$geometry' => $center->getGeoJSON(),
					'$maxDistance' => 500
				),
			),
			TAGS => 'amenity=pub',
		);
		$s = $c->find( $query )->limit( 5 );
		break;

	case 'hydepark': /* HYDEPARK and CAFES */
		$query = array( '_id' => array( '$in' => array( "n1696895511", "n1696895509", "n130210673", "n1696895513", "w157472706", "w19851241" ) ) );
		$s = $c->find( $query )->limit( 10 );
		break;

	case 'buildintersect': /* BUILDING and INTERSECTS */
		$building = $c->findOne( array( '_id' => "w4376720" ) );
		$query = array( 
			LOC => array( '$geoIntersects' => array( '$geometry' => $building['l'] ) ),
			TAGS => array( '$exists' => true ),
		);
		$s = $c->find( $query )->sort( array( 'l.type' => -1 ) );
		break;


	case 'aggr': /* CLOSESTS with DISTANCE (aggregation) */
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
				'query' => array( '$or' => array(
#			array( TAGS => 'amenity=pub' ),
#			array( TAGS => 'amenity=bar' )
					array( TAGS => 'amenity=restaurant' )
				) ),
			)
		) );
		$s = $res['result'];
		break;

	case 'withinbox': /* WITHIN box: */
		$query = array(
			LOC => array(
				'$within' => array(
					'$geometry' => array(
						'type' => 'Polygon',
						'coordinates' => array( array(
							array( -0.153191, 51.534199 ),
							array( -0.134630, 51.534199 ),
							array( -0.134630, 51.543759 ),
							array( -0.153191, 51.543759 ),
							array( -0.153191, 51.534199 )
						))
					)
				),
			),
			TAGS => 'amenity=pub',
		);
		$s = $c->find( $query );
		break;

	case 'timezone': /* TIMEZONE */
		$query = array(
			LOC => array(
				'$geoIntersects' => array(
					'$geometry' => $center->p,
				),
			),
		);
		// this finds the first TZID
		$s = $c->findOne( $query );

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
			$s = $c->find( $query );
		}
		else
		{
			$s = array();
		}
		break;

	default:
	case 'amenity':
		$query = array(
			LOC => array(
				'$near' => array(
					'$geometry' => $center->getGeoJSON(),
					'$maxDistance' => 500
				),
			),
			TAGS => array(
				'$in' => array(
					new MongoRegex( "/^amenity=/" ),
					new MongoRegex( "/^shop=/" ),
					new MongoRegex( "/^tourism=/" ),
				)
			)
		);
		$s = $c->find( $query )->limit( 400 );
		break;

}

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
		$name = $content = ''; $image = false;
		$classes = array();
		foreach ( $o[TAGS] as $tagName => $value ) {
			list( $tagName, $value ) = explode( '=', $value );
			if ( $tagName == 'name' ) {
				$name = $value; 
			} else if ( $tagName == 'title' ) {
				$name = $value; 
			} else if ( $tagName == 'thumb_url' ) {
				$ret['properties']['thumbUrl'] = $value;
			} else if ( $tagName == 'full_url' ) {
				$image = $value;
			} else {
				$content .= "<br/>{$tagName}: {$value}\n";
			}
			if ( in_array( $tagName, array( 'amenity', 'leisure' ) ) )
			{
				$classes[] = preg_replace( '/[^a-z0-9]/', '', $tagName . $value );
			}
		}
		if ($image) {
			$content = "<br/><div style='width: 500px'><img src='{$image}'/></div>";
		} else {
			$content .= "<br/><form action='checkin.php' method='post'><input type='hidden' name='object' value='{$o['_id']}'/><input type='submit' value='check in'/></form>";
		}
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
