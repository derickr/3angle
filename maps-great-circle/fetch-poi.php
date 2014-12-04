<?php
include '../config.php';
include '../classes.php';
include '../display.php';

ob_start("ob_gzhandler");

header('Content-type: text/plain');

ini_set('html_errors', 0);

$size = 0.95;

if ( isset( $_GET['size'] ) )
{
	$size = (float) $_GET['size'];
}

$n = (float) $_GET['n'];
$e = (float) $_GET['e'];
$s = (float) $_GET['s'];
$w = (float) $_GET['w'];
//var_dump( $n, $s, $e, $w );
//echo "\n";

$mNS = ($n+$s)/2;
$mEW = ($e+$w)/2;
//var_dump( $mNS, $mEW );
//echo "\n";

$n = $mNS + $size * ($mNS - $s);
$s = $mNS - $size * ($mNS - $s);
$w = $mEW + $size * ($mEW - $e);
$e = $mEW - $size * ($mEW - $e);
//var_dump( $n, $s, $e, $w );

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
define('SEGMENTS', array_key_exists('segments', $_GET) ? (int) $_GET['segments'] : 1);

for ($j = 0; $j < SEGMENTS; $j++ )
{
	$coordinates = [];
	for ( $i = 0; $i < 1; $i += 0.1 )
	{
		getPoint( $s, $w + (($e-$w)/SEGMENTS*$j), $s, $w + (($e-$w)/SEGMENTS*(1+$j)), $i, $lat, $lon );

		$coordinates[] = [ rad2deg($lon), rad2deg($lat) ];
	}
	$rets[] = [ 'l' => [ 'type' => 'LineString', 'coordinates' => $coordinates, ], ];
}

$coordinates = [];
for ( $i = 0; $i < 1; $i += 0.1 )
{
	getPoint( $s, $e, $n, $e, $i, $lat, $lon );

	$coordinates[] = [ rad2deg($lon), rad2deg($lat) ];
}
$rets[] = [ 'l' => [ 'type' => 'LineString', 'coordinates' => $coordinates, ], ];

for ($j = 0; $j < SEGMENTS; $j++ )
{
	$coordinates = [];
	for ( $i = 0; $i < 1; $i += 0.1 )
	{
		getPoint( $n, $w + (($e-$w)/SEGMENTS*$j), $n, $w + (($e-$w)/SEGMENTS*(1+$j)), $i, $lat, $lon );

		$coordinates[] = [ rad2deg($lon), rad2deg($lat) ];
	}
	$rets[] = [ 'l' => [ 'type' => 'LineString', 'coordinates' => $coordinates, ], ];
}

$coordinates = [];
for ( $i = 0; $i < 1; $i += 0.1 )
{
	getPoint( $n, $w, $s, $w, $i, $lat, $lon );

	$coordinates[] = [ rad2deg($lon), rad2deg($lat) ];
}
$rets[] = [ 'l' => [ 'type' => 'LineString', 'coordinates' => $coordinates, ], ];

$rets = format_response( $rets, false );

echo json_encode( $rets, JSON_PRETTY_PRINT );
?>
