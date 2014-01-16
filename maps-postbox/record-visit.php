<?php
include '../config.php';
include '../classes.php';
include '../display.php';
include '../tags.php';

ob_start("ob_gzhandler");

ini_set('display_errors', 1);
ini_set('html_errors', 0);
ini_set('error_reporting', -1);

header('Content-type: text/plain');
$m = new MongoClient( 'mongodb://localhost' );
$d = $m->selectDb( DATABASE );
$c = $d->selectCollection( COLLECTION );

$ref = trim( $_POST['ref'] );

$r = $c->update(
	[ '$and' => [ 
		[ TAGS => 'amenity=post_box' ],
		[ TAGS => "ref={$ref}" ]
	] ],
	[ '$set' => [ 'meta.visited' => true ] ]
);

var_dump( $ref, $_POST, $r );
?>
