<?php
include 'config.php';
include 'classes.php';

/* The file to parse will be on the command line */
$file = $argv[1];
if ( $argc == 3 )
{
	$collection = $argv[2];
}
else
{
	$collection = COLLECTION;
}

/* Connect, empty the collection and create indexes */
$mCache = new MongoClient( 'mongodb://localhost:27017/?w=1' );
//$mData = new MongoClient( 'mongodb://xdebug.org:27017/?w=1' );
$mData = new MongoClient( 'mongodb://localhost:27017/?w=1' );
$collection = $mData->selectCollection( DATABASE, $collection );
$collection->drop();
$collection->ensureIndex( array( TYPE => 1 ) );
$collection->ensureIndex( array( LOC => '2dsphere' ) );
$collection->ensureIndex( array( TAGS => 1 ) );

$cache = $mCache->selectCollection( "cache_" . DATABASE, 'nodecache' );
$cache->drop();

$cacheBatch = new MongoInsertBatch( $cache );
$insertBatch = new MongoInsertBatch( $collection );

/* Parse the nodes */
$z = new XMLReader();
$z->open($file);
while ($z->read() && $z->name !== 'node' );
$count = 0;
$collection->remove( array( TYPE => 1 ), array( 'wTimeoutMS' => 1800000 ) );
$cacheItems = array();
$collectionItems = array();

$filter = '/^(amenity=post_box)|(highway=)|(amenity=pub)|(amenity=bar)/';

echo "Importing nodes:\n";
while ($z->name === 'node') {
	$dom = new DomDocument;
	$node = simplexml_import_dom($dom->importNode($z->expand(), true));

	/* #1: Create the document structure */
	$q = array();
	/* Add type, _id and loc elements here */
	$q[TYPE] = 1;
	$q['_id'] = "n" . (string) $node['id'];
	$geo = new GeoJSONPoint( $node['lon'], $node['lat'] );
	$q[LOC] = $geo->getGeoJSON();
	/* Check the parseNode implementation */
	parseNode($q, $node);

	/* #2: Write the insert command here */
	if ( array_key_exists( TAGS, $q ) )
	{
		if ( !$filter || count( preg_grep( $filter, $q[TAGS] ) ) > 0 )
		{
			$insertBatch->add( $q );
		}
	}

	$cacheBatch->add( array( '_id' => (int) $node['id'], LOC => array( (float) $node['lon'], (float) $node['lat'] ) ) );

	$z->next('node');
	$count++;
	if ($count % 1000 === 0) {
		echo ".";
	}
	if ($count % 100000 === 0) {
		$cacheBatch->execute( [ 'w' => 1 ] );
		$insertBatch->execute( [ 'w' => 1 ] );
		echo "\n", $count, "\n";
	}
}

$cacheBatch->execute( [ 'w' => 1 ] );
$insertBatch->execute( [ 'w' => 1 ] );
echo "\n";

/* Parse the ways */
$z = new XMLReader();
$z->open($file);
while ($z->read() && $z->name !== 'way' );
$count = 0;
$collection->remove( array( TYPE => 2 ), array( 'timeout' => 1800000 ) );

echo "Importing ways:\n";
while ($z->name === 'way') {
	$currentCount = 0;
	$nodeIds = array();
	$locationCache = array();
	$ways = array();

	while ($z->name === "way" && $currentCount < 1000) {
		$dom = new DomDocument;
		$way = simplexml_import_dom($dom->importNode($z->expand(), true));
		recordNodeLinks( $nodeIds, $way );
		$ways[] = $way;
		$z->next('way');
		$currentCount++;
	}

	$locationCache = fetchLocationsForNodes( $cache, $nodeIds );

	if (count($ways) > 0) {
		$qs = array();
		foreach ( $ways as $way )
		{
			$q = array();
			$q['_id'] = "w" . (string) $way['id'];
			$q[TYPE] = 2;

			fetchLocations($collection, $q, $way, $locationCache );
			parseNode($q, $way);

			if ( !$filter || !is_array( $q[TAGS] ) || count( preg_grep( $filter, $q[TAGS] ) ) > 0 )
			{
				$insertBatch->add( $q );
			}
		}
	}

	$count += count($ways);
	if ($count % 100 === 0) {
		echo ".";
	}
	if ($count % 10000 === 0) {
		$insertBatch->execute( [ 'w' => 1 ] );
		echo "\n", $count, "\n";
	}
}
$insertBatch->execute( [ 'w' => 1 ] );
echo "\n";

relations_only:

/* Parse the relations */
$z = new XMLReader();
$z->open($file);
while ($z->read() && $z->name !== 'relation' );
$count = 0;
$collection->remove( array( TYPE => 3 ), array( 'timeout' => 1800000 ) );

echo "Importing relations:\n";
while ($z->name === 'relation') {
	$dom = new DomDocument;
	$relation = simplexml_import_dom($dom->importNode($z->expand(), true));

	/* #3: Create the document structure */
	$q = array();
	/* Add type and _id elements here */
	$q['_id'] = "r" . (string) $relation['id'];
	$q[TYPE] = 3;

	parseNode($q, $relation);
	if ( !fetchMembers($collection, $q, $relation, $idsToDelete ) )
	{
		goto nextrel;
	}

	try
	{
		$collection->insert( $q );
		foreach ( $idsToDelete as $idToDelete )
		{
			$collection->remove( array( '_id' => $idToDelete ) );
		}
	}
	catch ( MongoCursorException $e )
	{
		echo "\n", $q['_id'], ': ', $e->getMessage(), "\n";
		var_dump( $q );
	}

nextrel:
	$z->next('relation');
	if (++$count % 100 === 0) {
		echo ".";
	}
	if ($count % 10000 === 0) {
		echo "\n", $count, "\n";
	}
}
echo "\n";

norelations:


function fetchLocationsForNodes( $cache, $nodeIds )
{
	$locations = $tmp = array();

	$r = $cache->find( array( '_id' => array( '$in' => $nodeIds ) ) );
	foreach ( $r as $n ) {
		$tmp[$n["_id"]] = $n[LOC];
	}
	foreach ( $nodeIds as $id ) {
		if (isset($tmp[$id])) {
			$locations[ $id ] = $tmp[$id];
		}
	}

	return $locations;
}

function recordNodeLinks( &$nodeIds, $node )
{
	foreach ($node->nd as $nd) {
		$nodeIds[] = (int) $nd['ref'];
	}
}

function fetchLocations($collection, &$q, $node, $locationCache )
{
	$nodeIds = $locations = array();
	$currentLoc = null;

	foreach ($node->nd as $nd) {
		$nodeIds[] = (int) $nd['ref'];
		if ( array_key_exists( (int) $nd['ref'], $locationCache ) )
		{
			$locations[] = $locationCache[(int) $nd['ref']];
		}
	}

	if ( $nodeIds[0] == $nodeIds[sizeof( $nodeIds ) - 1] )
	{
		/* Extra array encapsulation to support outer/inner rings */
		$geo = new GeoJSONPolygon( array( $locations ) );
	}
	else
	{
		$geo = new GeoJSONLineString( $locations );
	}

	$q[LOC] = $geo->getGeoJSON();
}

function fetchMembers($collection, &$q, $node, &$idsToDelete)
{
	$tmp = $outerIds = $innerIds = $rings = array();
	$currentLoc = null;

	foreach ( $node->member as $member )
	{
		if ( $member['type'] != "way" )
		{
			/* Right now, we'll only handle way members */
			return false;
		}
		switch ( $member['role'] )
		{
			case 'outer':
				$outerIds[] = 'w' . (int) $member['ref'];
				break;
			case 'inner':
				$innerIds[] = 'w' . (int) $member['ref'];
				break;
			default:
				/* If it's not inner or other we don't do anything with it yet */
				return false;
		}
	}

	$r = $collection->find( array( '_id' => array( '$in' => $outerIds ) ) );
	foreach ( $r as $n )
	{
		$rings[] = GeoJSONPolygon::fromGeoJson( $n[LOC] )->pg[0];
	}

	$r = $collection->find( array( '_id' => array( '$in' => $innerIds ) ) );
	foreach ( $r as $n )
	{
		$rings[] = GeoJSONPolygon::fromGeoJson( $n[LOC] )->pg[0];
	}

	$geo = new GeoJSONPolygon( $rings );

	$q[LOC] = $geo->getGeoJSON();

	$idsToDelete = array_merge( $outerIds, $innerIds );
	return true;
}

function parseNode(&$q, $sxml)
{
	$tagsCombined = array();
	$ignoreTags = array( 'created_by', 'abutters' );

	$meta = array();
	if ( isset( $sxml['version'] ) )
	{
		$meta['v'] = (int) $sxml['version'];
	}
	if ( isset( $sxml['changeset'] ) )
	{
		$meta['cs'] = (int) $sxml['changeset'];
	}
	if ( isset( $sxml['uid'] ) )
	{
		$meta['uid'] = (int) $sxml['uid'];
	}
	if ( isset( $sxml['timestamp'] ) )
	{
		$meta['ts'] = (int) strtotime( $sxml['timestamp'] );
	}

	foreach( $sxml->tag as $tag )
	{
		if (!in_array( $tag['k'], $ignoreTags)) {
			$tagsCombined[] = (string) $tag['k'] . '=' . (string) $tag['v'];
		}
	}

	if ( sizeof( $tagsCombined ) > 0 )
	{
		$q[TAGS] = $tagsCombined;
	}
	if ( sizeof( $meta ) > 0 )
	{
		$q[META] = $meta;
	}
}
