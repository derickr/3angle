<?php
include 'config.php';

header('Content-type: text/plain');
$m = new MongoClient( 'mongodb://localhost' );
$d = $m->selectDb( DATABASE );

$wantedD = isset($_GET['d']) ? $_GET['d']: 1;

$query = array(
	TAGS => array(
		'$in' => array(
			new MongoRegex( "/^amenity=/" ),
			new MongoRegex( "/^shop=/" ),
			new MongoRegex( "/^tourism=/" ),
		)
	)
);

/* End */

/* This runs the geo search */
$s = $d->command(
	array(
		'geoNear' => COLLECTION,
		'spherical' => true,
		'near' => array(
			(float) $_GET['lon'],
			(float) $_GET['lat']
		),
		'num' => 250,
		'maxDistance' => $wantedD / 6371.01,
		'query' => $query,
	)
);

foreach( $s['results'] as $res)
{
	$o = $res['obj'];
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
		foreach ( $o[TAGS] as $tagName => $value ) {
			list( $tagName, $value ) = explode( '=', $value );
			if ( $tagName == 'name' ) {
				$name = $value; 
			} else {
				$content .= "<br/>{$tagName}: {$value}\n";
			}
		}
		$content .= "<br/><form action='checkin.php' method='post'><input type='hidden' name='object' value='{$o['_id']}'/><input type='submit' value='check in'/></form>";
		$ret['properties']['popupContent'] = "<b>{$name}</b>" . $content;
	}
	if ($o[TYPE] == 1) {
		$ret['geometry'] = array(
			'type' => "Point",
			'coordinates' => $o[LOC]
		);
	}
	if ($o[TYPE] == 2) {
		if ($o[LOC][0] == $o[LOC][count($o[LOC]) - 1]) {
			$ret['geometry'] = array(
				'type' => "Polygon",
				'coordinates' => array($o[LOC]),
			);
		} else {
			$ret['geometry'] = array(
				'type' => "LineString",
				'coordinates' => $o[LOC],
			);
		}
	}
	$rets[] = $ret;
}
echo json_encode( $rets, JSON_PRETTY_PRINT );
