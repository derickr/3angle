<?php
include 'config.php';

$lat = 51.508;
$lon = -0.128;
$zoom = 10;
$defaultLayers = array();

if ( isset( $_GET['lat'] ) )
{
	$lat = (float) $_GET['lat'];
}
if ( isset( $_GET['lon'] ) )
{
	$lon = (float) $_GET['lon'];
}
if ( isset( $_GET['zoom'] ) )
{
	$zoom = (float) $_GET['zoom'];
}
if ( isset( $_GET['l'] ) )
{
	$defaultLayers = explode( ',', $_GET['l'] );
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>OpenStreetMap in MongoDB example</title>
	<meta charset="utf-8" />
    <style>
body {
    padding: 0;
    margin: 0;
}
html, body, #map {
    height: 100%;
}
#search {
	z-index: 1000;
	position: absolute;
	top: 10px;
	right: 30px;
	width: 250px;
	background-color: white;
	border: 1px solid black;
	padding: 4px;
}
div.markerName {
	text-align: center;
	font-weight: bold;
	line-height: 1;
	margin-top: 1em;
}
div.leisurepark {
	text-align: center;
	vertical-align: middle;
	font-weight: bold;
	line-height: 1;
	opacity: 0.5;
	font-size: 4em;
	width: 900px;
	font-family: serif;
	margin-top: -0.5em;
}
    </style>

	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<link rel="stylesheet" href="leaflet.css" />
	<!--[if lte IE 8]><link rel="stylesheet" href="leaflet.ie.css" /><![endif]-->
	<link rel="stylesheet" href="Leaflet.markercluster/dist/MarkerCluster.Default.css" />
</head>

<body onLoad="changeLocation(false);">
	<div id="map"></div>

	<script type="text/javascript" src="leaflet.js"></script>
	<script type="text/javascript" src="jquery-1.7.2.min.js"></script>
	<script type="text/javascript" src="Leaflet.markercluster/dist/leaflet.markercluster.js"></script>

	<script>
		var map = new L.Map('map');
		var disabledRefetch;

		var OpenStreetMapUrl = 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
			OpenStreetMapAttribution = 'Map data &copy; 2013 OpenStreetMap contributors',
			OpenStreetMap = new L.TileLayer(OpenStreetMapUrl, {maxZoom: 18, attribution: OpenStreetMapAttribution, opacity: 0.7});

		map.setView(new L.LatLng(<?php echo $lat; ?>, <?php echo $lon; ?>), <?php echo $zoom; ?>).addLayer(OpenStreetMap); 

		var overlayer = new L.TileLayer('http://3angle/density.php?z={z}&x={x}&y={y}', {minZoom: 10, maxZoom: 14, opacity: 0.5});

<?php
foreach ( $layers as $layerName => $layerDir )
{
	$layerName = strtolower( $layerName );
	include "{$layerDir}/layer-def.js";

	if ( in_array( $layerName, $defaultLayers ) )
	{
		echo "{$layerName}Layer.addTo(map);\n";
	}
}
?>

		L.control.layers({"Base": OpenStreetMap}, {
<?php
$info = array();
foreach ( $layers as $layerName => $dummy )
{
	$lowLayerName = strtolower( $layerName );
	$info[] = "'$layerName': {$lowLayerName}Layer";
}
echo join( ", ", $info ), "\n";
?>
		}).addTo(map);

		map.on('autopanstart', function(event) {
			disabledRefetch = true;
		});

		map.on('moveend', changeLocation);

		var popup = new L.Popup();

		function calcCircleSize() {
			z = map.getZoom();
			return Math.min(12, Math.max(2, 15 - ((15-z) * 3)));
		}

		function calcCentre(coordinates) {
			var latN  = -90;
			var latS  = 90;
			var lonE  = -180;
			var lonW  = 180;

			coordinates.forEach( function (value) {
				latN = Math.max(latN, value[1]);
				latS = Math.min(latS, value[1]);
				lonE = Math.max(lonE, value[0]);
				lonW = Math.min(lonW, value[0]);
			} );

			return [(latN + latS) / 2, (lonE + lonW) / 2];
		}


		function changeLocation(event) {

			if (disabledRefetch) {
				disabledRefetch = false;
				return;
			}

			center = map.getCenter();
			bounds = map.getBounds();

<?php
foreach ( $layers as $layerName => $layerDir )
{
	$layerName = strtolower( $layerName );
	include "{$layerDir}/layer-action.js";
}
?>
		}
	</script>
</body>
</html>
