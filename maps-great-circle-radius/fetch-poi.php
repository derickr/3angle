<?php
include '../config.php';
include '../classes.php';
include '../display.php';

//ob_start("ob_gzhandler");

header('Content-type: text/plain');

ini_set('html_errors', 0);

$n = (float) $_GET['n'];
$e = (float) $_GET['e'];
$s = (float) $_GET['s'];
$w = (float) $_GET['w'];

$pLat = ($n + $s) / 2;
$pLng = ($e + $w) / 2;

if ( isset( $_GET['plat'] ) )
{
	$pLat = (float) $_GET['plat'];
}
if ( isset( $_GET['plng'] ) )
{
	$pLng = (float) $_GET['plng'];
}

$size = 0.95;

if ( isset( $_GET['size'] ) )
{
	$size = (float) $_GET['size'];
}

$rets = [];

$rets[] = [ 'l' => [ 'type' => 'Point', 'coordinates' => [ $pLng, $pLat ] ] ];

function getPoint( $cx, $cy, $s, $f, &$x, &$y )
{
	$x = $cx + $s * cos(deg2rad($f));
	$y = $cy + $s * sin(deg2rad($f));
}

for ($j = 0; $j < 360; $j += 15 )
{
	$coordinates = [];
	for ( $i = 0; $i <= 1; $i += 0.1 )
	{
		getPoint( $pLng, $pLat, $size, $j + ($i * 15), $x, $y);

		$coordinates[] = [ $x, $y ];
	}
	$rets[] = [ 'l' => [ 'type' => 'LineString', 'coordinates' => $coordinates, ], ];
}

$rets = format_response( $rets, false );

echo json_encode( $rets, JSON_PRETTY_PRINT );
?>
