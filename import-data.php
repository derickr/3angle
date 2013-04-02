<?php
include 'config.php';
include 'classes.php';

/* The file to parse will be on the command line */
$file = $argv[1];

/* Connect, empty the collection and create indexes */
$m = new MongoClient( 'mongodb://localhost:27017' );
$collection = $m->selectCollection( DATABASE, COLLECTION );
$collection->drop();
$collection->ensureIndex( array( LOC => '2dsphere' ) );
$collection->ensureIndex( array( TAGS => 1 ) );

/* Parse the nodes */
$z = new XMLReader();
$z->open( $argv[1]);
while ($z->read() && $z->name !== 'node' );
$count = 0;
$collection->remove( array( 'type' => 1 ) );

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
	$collection->insert( $q );

	$z->next('node');
	$count++;
	if ($count % 1000 === 0) {
		echo ".";
	}
	if ($count % 100000 === 0) {
		echo "\n", $count, "\n";
	}
}
echo "\n";

/* Parse the ways */
$z = new XMLReader();
$z->open( $argv[1]);
while ($z->read() && $z->name !== 'way' );
$count = 0;
$collection->remove( array( 'type' => 2 ) );

echo "Importing ways:\n";
while ($z->name === 'way') {
	$dom = new DomDocument;
	$way = simplexml_import_dom($dom->importNode($z->expand(), true));

	/* #3: Create the document structure */
	$q = array();
	/* Add type and _id elements here */
	$q['_id'] = "w" . (string) $way['id'];
	$q[TYPE] = 2;
	/* Check the fetchLocations() and parseNode() implementations */
	fetchLocations($collection, $q, $way);
	parseNode($q, $way);

	/* #4: Write the insert command here */
	$collection->insert( $q );

	$z->next('way');
	if (++$count % 100 === 0) {
		echo ".";
	}
	if ($count % 10000 === 0) {
		echo "\n", $count, "\n";
	}
}
echo "\n";

function fetchLocations($collection, &$q, $node)
{
	$tmp = $locations = $nodeIds = array();
	foreach ($node->nd as $nd) {
		$nodeIds[] = 'n' . (int) $nd['ref'];
	}
	$r = $collection->find( array( '_id' => array( '$in' => $nodeIds ) ) );
	foreach ( $r as $n ) {
		$tmp[$n["_id"]] = GeoJSONPoint::fromGeoJson( $n[LOC] )->p;
	}
	foreach ( $nodeIds as $id ) {
		if (isset($tmp[$id])) {
			$locations[] = $tmp[$id];
		}
	}
	if ( $nodeIds[0] == $nodeIds[sizeof( $nodeIds ) - 1] )
	{
		$geo = new GeoJSONPolygon( $locations );
	}
	else
	{
		$geo = new GeoJSONLineString( $locations );
	}
	$q[LOC] = $geo->getGeoJSON();
}

function parseNode(&$q, $sxml)
{
	$tagsCombined = array();
	$ignoreTags = array( 'created_by', 'abutters' );

	foreach( $sxml->tag as $tag )
	{
		if (!in_array( $tag['k'], $ignoreTags)) {
			$tagsCombined[] = (string) $tag['k'] . '=' . (string) $tag['v'];
		}
	}

	$q[TAGS] = $tagsCombined;
}
