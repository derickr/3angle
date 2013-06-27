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

	case 'photos':
		$c = $d->selectCollection( 'flickr' );
		$query = array(
			LOC => array(
				'$near' => array(
					'$geometry' => $center->getGeoJSON(),
				),
			),
		);
		$s = $c->find( $query )->limit( 10000 );
		break;

	case 'pubsnosmoke': /* FIVE CLOSEST PUBS */
		$query = array(
			LOC => array(
				'$near' => array(
					'$geometry' => $center->getGeoJSON(),
					'$maxDistance' => 2500
				),
			),
			'$or' => array( array( TAGS => 'smoking=separated', TAGS => 'smoking=isolated', TAGS => 'smoking=no' ) ),
		);
		$s = $c->find( $query )->limit( 500 );
		break;

	case 'pubs': /* FIVE CLOSEST PUBS */
		$query = array(
			LOC => array(
				'$near' => array(
					'$geometry' => $center->getGeoJSON(),
					'$maxDistance' => 2500
				),
			),
			TAGS => 'amenity=pub',
		);
		$s = $c->find( $query )->limit( 500 );
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
					[ '$geoNear' => [
						'near' => $center->getGeoJSON(),
						'distanceField' => 'd',
						'distanceMultiplier' => 1,
						'maxDistance' => 22000,
						'spherical' => true,
						'query' => [
							TYPE => [ '$gte' => 2 ],
						],
						'limit' => 4,
					] ],
					[ '$sort' => [ 'd' => 1 ] ],
					[ '$limit' => 1 ]
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
					sprintf( "TZID=UTC%s%d", $sign, abs($offset) )
				),
			);
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
//			} else if ( $tagName == 'thumb_url' ) {
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
			$content = "<br/><div style='width: 150px'><img src='{$image}'/></div>";
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
