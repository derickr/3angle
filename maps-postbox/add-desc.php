<?php
include '../config.php';
include '../classes.php';
include '../display.php';
include '../tags.php';
include '../description.php';

$m = new MongoClient( 'mongodb://localhost' );
$m = new MongoClient( 'mongodb://xdebug.org' );

$cr = $m->selectCollection( DATABASE, COLLECTION );

$cur = $cr->find( [ 
	TAGS => 'amenity=post_box', 
	'$or' => [
		[ 'descV' => [ '$lt' => DESC_VERSION ] ],
		[ 'descV' => [ '$exists' => false ] ]
	]
] );

foreach ( $cur as $r )
{
	$tags = Functions::split_tags( $r[TAGS] );

	if ( isset( $tags['ref'] ) )
	{
		echo "REF={$tags['ref']}; ";
	}
	else
	{
		echo "REF=???; ";
	}
	$desc = createDescription( $cr, $r );
	echo $desc, "\n";

	$cr->update( [ '_id' => $r['_id'] ], [ '$set' => [ 'descV' => DESC_VERSION, 'desc' => $desc ] ] );
}
