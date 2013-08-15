<?php
define( 'TAGS', 'ts' );
define( 'LOC', 'l');

ob_start("ob_gzhandler");  

include '../display.php';

$location = $_GET['location'];
//$location = 'London, United Kingdom';

function doGeoCode( $location, &$lon, &$lat )
{
	if ( preg_match( '/^lon:(.*),lat:(.*)$/', $location, $m ) )
	{
		$lon = (float) $m[1];
		$lat = (float) $m[2];

		return true;
	}
	$location = urlencode( $location );
	$geoCode = json_decode( file_get_contents( "http://nominatim.openstreetmap.org/search?q={$location}&format=json&limit=1" ) );

	$lon = (float) $geoCode[0]->lon;
	$lat = (float) $geoCode[0]->lat;

	return true;
}

function fetchMeetup( &$docs, $lon, $lat )
{
	$opts = array(
		'http'=>array(
			'header'=>"Accept-Charset: utf-8\r\n"
		)
	);

	$context = stream_context_create($opts);

	$url = "https://api.meetup.com/2/open_events?&sign=true&lon={$lon}&lat={$lat}&text=mongodb%20mongo&radius=9999&order=distance&page=100&key=165954184b2141c132f3a505e2d396c";
	$data = file_get_contents( $url, false, $context );
	$json = json_decode( $data );
	foreach ( $json->results as $event )
	{
		$doc = [];
		$doc['id'] = $event->id;
		$doc['name'] = $event->name;
		$doc['description'] = substr( strip_tags( $event->description ), 0, 500 );

		if ( ! preg_match( '/\bmongo(db)?\b/i', $doc['description'] ) )
		{
			continue;
		}

		$doc['when']['start'] = strtotime( "@" . $event->time / 1000 );
		$doc['url'] = $event->event_url;
		$doc['venue'] = $event->venue;
		$doc['l'] = [ 'type' => 'Point', 'coordinates' => [ $event->venue->lon, $event->venue->lat ] ] ;

		foreach ( array( 'name', 'description', 'url' ) as $name )
		{
			$doc[TAGS][] = "{$name}={$doc[$name]}";
		}
		$doc[TAGS][] = "at=" . date_create( "@{$doc['when']['start']}" )->format( 'Y-m-d H:i:s' );
		$doc[TAGS][] = "venue=" . join( ', ', (array) $event->venue );

		if ( $event->venue->lon && $event->venue->lat )
		{
			$docs[] = $doc;
		}
	}
}

function fetchEventBrite( &$docs, $lon, $lat )
{
	$url = "https://www.eventbrite.com/json/event_search?app_key=EHHWMU473LTVEO4JFY&within=9999&latitude={$lat}&longitude={$lon}";
	$json = json_decode( file_get_contents( $url ) );

	foreach ( $json->events as $event )
	{
		if ( isset( $event->summary ) )
		{
			continue;
		}
	
		$event = $event->event;

		if ( ! preg_match( '/\bmongo(db)?\b/i', $doc['description'] ) )
		{
			continue;
		}

		$doc = [];
		$doc['id'] = $event->id;
		$doc['name'] = $event->title;
		$doc['description'] = substr( strip_tags( $event->description ), 0, 500 );
		$doc['when']['start'] = strtotime( $event->start_date );
		$doc['when']['end']   = strtotime( $event->end_date );
		$doc['url'] = $event->url;
		$doc['venue'] = $event->venue;
		$doc['l'] = [ 'type' => 'Point', 'coordinates' => [ $event->venue->lon, $event->venue->lat ] ] ;

		foreach ( array( 'name', 'description', 'url' ) as $name )
		{
			$doc[TAGS][] = "{$name}={$doc[$name]}";
		}
		$doc[TAGS][] = "at=" . date_create( "@{$doc['when']['start']}" )->format( 'Y-m-d H:i:s' );
		$doc[TAGS][] = "venue=" . join( ', ', (array) $event->venue );

		$doc['l'] = [ 'type' => 'Point', 'coordinates' => [ $event->venue->longitude, $event->venue->latitude ] ] ;

		if ( $event->venue->longitude && $event->venue->latitude )
		{
			$docs[] = $doc;
		}
	}
}

$key = '10genevent-' . base64_encode( $location );
if ( file_exists( '/tmp/' . $key ) )
{
	echo file_get_contents( "/tmp/{$key}" );
	die();
}

if ( doGeoCode( $location, $lon, $lat ) )
{
	$events = [];
	fetchMeetup( $events, $lon, $lat );
	fetchEventBrite( $events, $lon, $lat );

	header( 'Content-Type: text/json' );
	$json = json_encode( format_response( $events, false ), JSON_PRETTY_PRINT );
	file_put_contents( "/tmp/{$key}", $json );
	echo $json;
	die();
}
