<?php
$sqlfile = __DIR__ . '/../../tz-tiles.mbtiles';

$db = new PDO(
	"sqlite:/{$sqlfile}",
	null,
	null,
	array(PDO::ATTR_PERSISTENT => true)
); 

$x = (int) $_GET['x'];
$y = (int) $_GET['y'];
$z = (int) $_GET['z'];

$y = pow(2, $z) - $y - 1;

$sql = "SELECT tile_data FROM tiles WHERE zoom_level = {$z} AND tile_row = {$y} AND tile_column = {$x}";
$cmd = $db->prepare($sql);
if ($cmd->execute()) {
	$r = $cmd->fetchAll();
	header('Content-type: image/png');
	echo $r[0]['tile_data'];
}
?>
