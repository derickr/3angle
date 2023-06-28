<?php
include 'functions.php';

$triangle = new Triangle();
$triangle->commit();
header( 'Location: /index.php?zoom=17&l=threeangle&' . $triangle->getLocation() );
?>
