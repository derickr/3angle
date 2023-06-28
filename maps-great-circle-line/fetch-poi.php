<?php
include '../config.php';
include '../classes.php';
include '../display.php';

ob_start("ob_gzhandler");

header('Content-type: text/plain');

ini_set('html_errors', 0);

$lat1 = (float) $_GET['lat1'];
$lon1 = (float) $_GET['lon1'] + 90;
$lat2 = (float) $_GET['lat2'];
$lon2 = (float) $_GET['lon2'] + 90;

$rets = [];

/*
	d=2*asin(
		sqrt(
			(sin((lat1-lat2)/2))^2 +
			cos(lat1)*cos(lat2)*(sin((lon1-lon2)/2))^2
		)
	)

	d=acos(sin(lat1)*sin(lat2)+cos(lat1)*cos(lat2)*cos(lon1-lon2))
 */
function distance( $n, $e, $s, $w, &$d )
{
	$lat1 = deg2rad( $n );
	$lat2 = deg2rad( $s );
	$lon1 = deg2rad( $w );
	$lon2 = deg2rad( $e );

	$d = acos(
		sin($lat1) * sin($lat2) +
		cos($lat1) * cos($lat2) * cos($lon2 - $lon1)
	);
}

/*
	A=sin((1-f)*d)/sin(d)
	B=sin(f*d)/sin(d)
	x = A*cos(lat1)*cos(lon1) +  B*cos(lat2)*cos(lon2)
	y = A*cos(lat1)*sin(lon1) +  B*cos(lat2)*sin(lon2)
	z = A*sin(lat1)           +  B*sin(lat2)
	lat=atan2(z,sqrt(x^2+y^2))
	lon=atan2(y,x)
*/
function getPoint( $n, $e, $s, $w, $f, &$lat, &$lon )
{
	$lat1 = deg2rad( $n );
	$lat2 = deg2rad( $s );
	$lon1 = deg2rad( $w );
	$lon2 = deg2rad( $e );

	distance( $n, $e, $s, $w, $d );

	$A = sin( ( 1 -$f ) * $d ) / sin( $d );
	$B = sin( $f * $d ) / sin( $d );
	$x = $A * cos($lat1) * cos($lon1) + $B * cos($lat2) * cos($lon2);
	$y = $A * cos($lat1) * sin($lon1) + $B * cos($lat2) * sin($lon2);
	$z = $A * sin($lat1) + $B * sin($lat2);

	$lat = atan2( $z, sqrt( $x * $x + $y * $y ) );
	$lon = atan2( $y, $x );
}
/*
distance( $n, $w, $s, $w, $d );
var_dump( $n, $w, $s, $w, $d );

distance( $n, $e, $s, $e, $d );
var_dump( $n, $e, $s, $e, $d );
die();
*/

define('SEGMENTS', 1);

function doLine( &$rets, $lat1, $lon1, $lat2, $lon2 )
{
	$step = 1 / (array_key_exists('segments', $_GET) ? (int) $_GET['segments'] : 1) - 0.00001;
	for ($j = 0; $j < SEGMENTS; $j++ )
	{
		$coordinates = [];
		for ( $i = 0; $i < 1; $i += $step )
		{
			getPoint( $lat2, $lon1 + (($lon2-$lon1)/SEGMENTS*$j), $lat1, $lon1 + (($lon2-$lon1)/SEGMENTS*(1+$j)), $i, $lat, $lon );

			$coordinates[] = [ rad2deg($lon), rad2deg($lat) ];
		}
		$rets[] = [ 'l' => [ 'type' => 'LineString', 'coordinates' => $coordinates, ], ];
	}
}
/*
doLine($rets, 8,  1, 8, 12);
doLine($rets, 3,  1, 8,  1);
doLine($rets, 3, 12, 8, 12);

doLine($rets, 8.02, 6, 8.02, 7);
doLine($rets, 8.02, 6, 9, 6);
doLine($rets, 8.02, 7, 9, 7);
*/
doLine($rets, $lat1, $lon1, $lat2, $lon2);

$rets = format_response( $rets, false );

echo json_encode( $rets, JSON_PRETTY_PRINT );
?>
