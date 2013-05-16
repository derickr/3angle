<?php
include 'config.php';
include 'classes.php';
include '/home/derick/dev/osm-tools/lib/convert.php';

$m = new MongoClient( 'mongodb://localhost' );
$d = $m->selectDb( DATABASE );
$c = $d->selectCollection( COLLECTION );

$steps = 12;

$im = imagecreatetruecolor(256, 256);

$bb = tile2boundingbox( $_GET['x'], $_GET['y'], $_GET['z'] );
$white = imagecolorallocate( $im, 255, 255, 255 );

$colours = array();
$max = pow(4, (15-$_GET['z'])) * 32;
for ( $i = 0; $i <= 10; $i++ )
{
	$colours[$i] = imagecolorallocate( $im, 25.5 * $i, 25.5 * $i, 25.5 * $i );
}

$points = array();
$points[] = array( $bb['west'], $bb['north'] );
$points[] = array( $bb['east'], $bb['north'] );
$points[] = array( $bb['east'], $bb['south'] );
$points[] = array( $bb['west'], $bb['south'] );
$points[] = array( $bb['west'], $bb['north'] );

$query = array(
	LOC => array(
		'$within' => array(
			'$geometry' => array(
				'type' => 'Polygon',
				'coordinates' => array( $points ),
			)
		),
	),
//	TAGS => 'amenity=pub',
);
$items = iterator_to_array( $c->find( $query ) );

for ( $x = 0; $x < $steps; $x++ )
{
	for ( $y = 0; $y < $steps; $y++ )
	{
		$s = 0;

		foreach ( $items as $item )
		{
			if ($item[TYPE] == 1) {
/*
				printf("%f %f %f %f<br/>\n",
					$bb['west'] + ($x     / $steps) * ($bb['east'] - $bb['west'] ),
					$bb['west'] + (($x+1) / $steps) * ($bb['east'] - $bb['west'] ),
					$bb['north'] + ($y     / $steps) * ($bb['south'] - $bb['north'] ),
					$bb['north'] + (($y+1) / $steps) * ($bb['south'] - $bb['north'] )
				);

				printf("%f %f<br/>\n", $item[LOC]['coordinates'][0], $item[LOC]['coordinates'][1] );
*/
				if (
					( $item[LOC]['coordinates'][0] >= $bb['west'] + ($x     / $steps) * ($bb['east'] - $bb['west'] ) ) &&
					( $item[LOC]['coordinates'][0] <  $bb['west'] + (($x+1) / $steps) * ($bb['east'] - $bb['west'] ) ) &&
					( $item[LOC]['coordinates'][1] <= $bb['north'] + ($y     / $steps) * ($bb['south'] - $bb['north'] ) ) &&
					( $item[LOC]['coordinates'][1] >  $bb['north'] + (($y+1) / $steps) * ($bb['south'] - $bb['north'] ) )
				) {
					$s++;
				}
			}
		}
		/*
		$points[] = array( $bb['west'] + ($x     / $steps) * ($bb['east'] - $bb['west'] ), $bb['north'] + ($y     / $steps ) * ($bb['south'] - $bb['north'] ) );
		$points[] = array( $bb['west'] + (($x+1) / $steps) * ($bb['east'] - $bb['west'] ), $bb['north'] + ($y     / $steps ) * ($bb['south'] - $bb['north'] ) );
		$points[] = array( $bb['west'] + (($x+1) / $steps) * ($bb['east'] - $bb['west'] ), $bb['north'] + (($y+1) / $steps ) * ($bb['south'] - $bb['north'] ) );
		$points[] = array( $bb['west'] + ($x     / $steps) * ($bb['east'] - $bb['west'] ), $bb['north'] + (($y+1) / $steps ) * ($bb['south'] - $bb['north'] ) );
		$points[] = array( $bb['west'] + ($x     / $steps) * ($bb['east'] - $bb['west'] ), $bb['north'] + ($y     / $steps ) * ($bb['south'] - $bb['north'] ) );
		*/

//		printf( "%s %f %d<br/>\n", $s, $max, (int) (($s / $max) * 10)); flush();
		if ( $s > $max )
		{
			$colour = $colours[10];
		}
		else
		{
			$colour = $colours[(int) (($s / $max) * 10)];
		}

		imagefilledrectangle( $im, ($x / $steps) * 256, ($y / $steps) * 256, (($x+1) / $steps) * 256 - 1, (($y+1) / $steps) * 256 - 1, $colour );
		/*
		imagestring(
			$im,
			2,
			($x / $steps) * 256 + 2,
			($y / $steps) * 256 + 2,
			$s,
			$white
		);
		*/
	}
}

/*
imagestring( $im, 2, 100,   2, $bb['north'], $white );
imagestring( $im, 2, 100, 240, $bb['south'], $white );
imagestring( $im, 2,   2, 120, $bb['west'], $white );
imagestring( $im, 2, 200, 120, $bb['east'], $white );
*/

header('Content-Type: image/png');
imagepng( $im );
