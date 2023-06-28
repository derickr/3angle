<?php
include '../config.php';
include '../classes.php';
include '../display.php';

ob_start("ob_gzhandler");

ini_set('display_errors', 1);
ini_set('error_reporting', -1);

header('Content-type: text/plain');
$m = new \MongoDB\Driver\Manager( 'mongodb://localhost:27016' );

$cmd = new \MongoDB\Driver\Command( [ 'distinct' => 'flickr', 'key' => 'sets' ] );
$r = iterator_to_array( $m->executeCommand( DATABASE, $cmd ) );
$sets = $r[0]->values;

natcasesort( $sets ); 
$sets = array( "all" ) + array_values( $sets );

echo json_encode( $sets, JSON_PRETTY_PRINT );
?>
