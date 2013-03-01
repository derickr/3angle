<?php
include 'functions.php';

$triangle = new Triangle();
$triangle->run();
?>
<!DOCTYPE html>
<html>
<head>
<title>Check-in</title>
<style>
body {
	font-size: 14pt;
}
td {
	padding: 2.5pt;
}
    </style>

	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<link rel="stylesheet" href="leaflet.css" />
	<!--[if lte IE 8]><link rel="stylesheet" href="leaflet.ie.css" /><![endif]-->
</head>
</head>
<body>
<h1><?php echo $triangle->title; ?></h1>

<form action="commit.php">
<?php echo $triangle->show_data(); ?>
</form>
</body>
</html>
