<?php
include 'functions.php';
include 'field_info.inc';

//ob_start("ob_gzhandler");

ini_set('display_errors', 1);
ini_set('error_reporting', -1);

header('Content-type: text/plain');
$m = new MongoClient( 'mongodb://localhost' );
$d = $m->selectDb( DATABASE );
$c = $d->selectCollection( COLLECTION );

$query = array(
	TAGS => array(
		'$in' => array(
			new MongoRegex( "/^amenity=/" ),
			new MongoRegex( "/^shop=/" ),
			new MongoRegex( "/^tourism=/" ),
		)
	)
);

$s = $c->find( $query );

foreach ( $s as $item )
{
	$tags = Functions::split_tags( $item[TAGS] );
	$score = calc_score( $tags, $fieldInfo, $rules );
	if ( $score !== false )
	{
		$c->update(
			array( '_id' => $item['_id'] ),
			array( '$set' => array( 'score' => $score ) )
		);
	}
}

function calc_score( $tags, $fieldInfo, $rules )
{
	$key = false;
	if ( array_key_exists( 'amenity', $tags ) )
	{
		$key = 'amenity=' . $tags['amenity'];
	}
	if ( array_key_exists( 'shop', $tags ) )
	{
		$key = 'shop=' . $tags['shop'];
	}
	if ( array_key_exists( 'tourism', $tags ) )
	{
		$key = 'tourism=' . $tags['tourism'];
	}
	if ( $key === false )
	{
		return false;
	}
	if ( !array_key_exists( $key, $rules ) )
	{
		echo "Unknown key '$key'\n";
		/* check for fallback '*=' rules */
		if ( array_key_exists( 'amenity', $tags ) )
		{
			$key = 'amenity=';
		}
		if ( array_key_exists( 'shop', $tags ) )
		{
			$key = 'shop=';
		}
		if ( array_key_exists( 'tourism', $tags ) )
		{
			$key = 'tourism=';
		}

		if ( !array_key_exists( $key, $rules ) )
		{
			echo "XX Unknown key '$key'\n";
			return false;
		}
	}

	$max = 0;
	$count = 0;

	if ( sizeof( $rules[$key] ) == 0 )
	{
		return 100;
	}

	foreach ( $rules[$key] as $key => $opts )
	{
		$currentScore = 10;
		if ( array_key_exists( $key, $fieldInfo ) )
		{
			$currentScore = $fieldInfo[$key];
		}
		$max += $currentScore;
		if ( array_key_exists( $key, $tags ) )
		{
			$count += $currentScore;
		}
	}

	return (int) ( ( $count / $max ) * 100 );
}
