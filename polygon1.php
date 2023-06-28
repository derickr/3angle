<?php
include 'classes.php';

/*
/maps-flickr/fetch-poi.php?n=65.91062334197893&e=31.113281249999996&s=46.86019101567027&w=-20.91796875
http://127.0.1.4/?l=flickr&lat=53&lon=6.5&zoom=4
http://127.0.1.4/?l=flickr&lat=57&lon=6.5&zoom=4
http://127.0.1.4/?l=flickr,gc&lat=57&lon=6.5&zoom=4
http://127.0.1.4/?l=flickr,gc&lat=60&lon=6.5&zoom=3
http://127.0.1.4/?l=flickr,gc&lat=60&lon=6.5&zoom=3&segments=5
*/

$_GET = array(
    'n' => 65.44000165965534,
    'e' => 32.51953125,
    's' => 46.07323062540838,
    'w' => -19.51171875,
);

$polygon = GeoJSONPolygon::createFromBounds(
    min( 90, (float) $_GET['n'] ),
    min( 180, (float) $_GET['e']) ,
    max( -90, (float) $_GET['s'] ),
    max( -180, (float) $_GET['w'] )
);

var_dump($polygon);
?>
