<?php
include 'classes.php';

// defines
define( 'TAGS', 'ts' );

// connect and select database 'demo' and collection 'timezone'
$m = new MongoClient;
$d = $m->demo;
$tzc = $d->selectCollection( 'timezone' );

$s = $tzc->find( array( TAGS => 'TZID=Europe/Amsterdam' ) );

foreach ( $s as $part )
{
	var_dump( $part );
}
?>
