<?php
include 'config.php';

$lat = 51.508;
$lon = -0.128;
$zoom = 10;
$maxZoom = 19;
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
$mapUrl = 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
if ( isset( $_GET['kb'] ) )
{
	$maxZoom = 4;
	$mapUrl = 'http://127.0.1.4/images/kerbin_project/{z}/{x}/{y}.png';
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
#info {
	z-index: 1000;
	position: absolute;
	bottom: 25px;
	left: 30px;
}
#flickrInfo {
	background-color: rgba(255,255,255,0.5);
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
	<link rel="stylesheet" href="Leaflet.markercluster/MarkerCluster.Default.css" />
</head>

<body onLoad="changeLocation(false);">
	<div id="map"></div>
	<div id="info">
<?php
foreach ( $layers as $layerName => $info )
{
	if ( file_exists( "{$info['directory']}/info-box.html" ) )
	{
		include "{$info['directory']}/info-box.html";
	}
}
?>
	</div>

	<script type="text/javascript" src="leaflet.js"></script>
	<script type="text/javascript" src="jquery-1.7.2.min.js"></script>
	<script type="text/javascript" src="Leaflet.markercluster/leaflet.markercluster.js"></script>

	<script>
		$.urlParam = function(name){
			var results = new RegExp('[\\?&#]' + name + '=([^&]*)').exec(window.location.hash);
			if (results) {
				return results[1];
			}
			return false;
		}

		var map = new L.Map('map');
		var disabledRefetch;

		var OpenStreetMapUrl = '<?php echo $mapUrl; ?>',
			OpenStreetMapAttribution = 'Map data &copy; 2013 OpenStreetMap contributors',
			OpenStreetMap = new L.TileLayer(OpenStreetMapUrl, {maxZoom: <?php echo $maxZoom; ?>, attribution: OpenStreetMapAttribution, opacity: 0.7, tms: false});

		map.setView(new L.LatLng(<?php echo $lat; ?>, <?php echo $lon; ?>), <?php echo $zoom; ?>).addLayer(OpenStreetMap); 

<?php
foreach ( $layers as $layerName => $info )
{
	include "{$info['directory']}/layer-def.js";

	if ( in_array( $info['layerName'], $defaultLayers ) )
	{
		echo "{$info['layerName']}Layer.addTo(map);\n";
	}
}
?>

		L.control.layers({"Base": OpenStreetMap}, {
<?php
$includeLayers = array();
foreach ( $layers as $layerName => $info )
{
	$includeLayers[] = "'$layerName': {$info['layerName']}Layer";
}
echo join( ", ", $includeLayers ), "\n";
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
foreach ( $layers as $layerName => $info )
{
	if ( file_exists( "{$info['directory']}/layer-action.js" ) )
	{
		include "{$info['directory']}/layer-action.js";
	}
}
?>
		}
	</script>
</body>
</html>
