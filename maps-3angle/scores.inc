<?php
$scores = array(
	'name' => 50,
	'addr:housenumber' => 25,
	'addr:housename' => 25,
	'addr:street' => 25,
	'addr:postcode' => 20,
	'post_code' => 20,
	'cuisine' => 20,
	'real_ale' => 15,
	'real_cider' => 15,
	'wheelchair' => 15,
	'opening_hours' => 10,
);

$rules = array(
	'amenity=restaurant' => array(
		'name' => array(),
		'addr:street' => array(),
		'addr:housenumber' => array( 'alternative' => 'addr:housename' ),
		'addr:postcode' => array( 'alternative' => 'post_code' ),
		'cuisine' => array(),
		'wheelchair' => array(),
	),
	'amenity=pub' => array(
		'name' => array(),
		'addr:street' => array(),
		'addr:housenumber' => array( 'alternative' => 'addr:housename' ),
		'addr:postcode' => array( 'alternative' => 'post_code' ),
		'real_ale' => array(),
		'real_cider' => array(),
		'wheelchair' => array(),
	),
);

$optional = array(
	'addr:city', 'addr:country',
);

