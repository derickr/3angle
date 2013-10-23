<?php
$fieldInfo = array(
	'name' => 50,
	'operator' => 30,
	'addr:housenumber' => 25,
	'addr:housename' => 25,
	'addr:street' => 25,
	'addr:postcode' => 20,
	'post_code' => 20,
	'cuisine' => 20,
	'atm' => 20,
	'fee' => 20,
	'real_ale' => 15,
	'real_cider' => 15,
	'wheelchair' => 15,
	'opening_hours' => 10,
);

$_address = array(
	'addr:street' => array(),
	'addr:housenumber' => array( 'alternative' => 'addr:housename' ),
	'addr:postcode' => array( 'alternative' => 'post_code' ),
);

$rules = array();

/* Generic fallback types */
$rules['amenity='] = array_merge(
	array(
		'name' => array(),
		'wheelchair' => array(),
	),
	$_address
);
$rules['shop='] = $rules['amenity='];
$rules['tourism='] = $rules['amenity='];

/* Ignores */
$rules['amenity=bench'] = array();
$rules['amenity=bicycle_parking'] = array();


$rules['amenity=atm'] = array(
	'operator' => array(),
	'fee' => array(),
);

$rules['amenity=bank'] = array_merge(
	$rules['amenity='],
	array(
		'atm' => array(),
	)
);

$rules['amenity=restaurant'] = array_merge(
	$rules['amenity='],
	array(
		'cuisine' => array(),
		'wheelchair' => array(),
	)
);
$rules['amenity=cafe'] = $rules['amenity=restaurant'];

$rules['amenity=pub'] = array_merge(
	$rules['amenity='],
	array(
		'real_ale' => array(),
		'real_cider' => array(),
		'food' => array(),
	)
);
$rules['amenity=bar'] = $rules['amenity=pub'];
$rules['amenity=biergarten'] = $rules['amenity=pub'];

$optional = array(
	'addr:city', 'addr:country',
);

