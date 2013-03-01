<?php
include 'functions.php';

$triangle = new Triangle();
$triangle->commit();
header( 'Location: /index.php?' . $triangle->getLocation() );
?>
