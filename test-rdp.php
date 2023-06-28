<?php
include 'rdp.php';

$points = [
	[  0,  10 ],
	[  1,   6 ],
	[  3,   0 ],
	[  5,   6 ],
	[  7,   8 ],
	[  8,   7 ],
	[  9,   4 ],
	[  13, 10 ],
];

var_dump( RDP::Simplify( $points, $argv[1] ) );
