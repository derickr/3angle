<?php
$lat = 51.508;
$lon = -0.128;
if ( isset( $_GET['lat'] ) )
{
	$lat = (float) $_GET['lat'];
}
if ( isset( $_GET['lon'] ) )
{
	$lon = (float) $_GET['lon'];
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
</head>

<body onLoad="changeLocation(false);">
	<div id="map"></div>

	<script type="text/javascript" src="leaflet.js"></script>
	<script type="text/javascript" src="jquery-1.7.2.min.js"></script>

	<script>
		var map = new L.Map('map');

		var OpenStreetMapUrl = 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
			OpenStreetMapAttribution = 'Map data &copy; 2011 OpenStreetMap contributors',
			OpenStreetMap = new L.TileLayer(OpenStreetMapUrl, {maxZoom: 18, attribution: OpenStreetMapAttribution, opacity: 0.7});

		map.setView(new L.LatLng(<?php echo $lat; ?>, <?php echo $lon; ?>), 17).addLayer(OpenStreetMap);

		var geojsonMarkerOptions = {
			radius: 12,
			fillColor: "#8888ff",
			color: "#33f",
			weight: 1,
			opacity: 1,
			fillOpacity: 0.6
		};
		var geojsonLineOptions = {
			fillColor: "#8888ff",
			color: "#33f",
			weight: 3,
			opacity: 1,
			width: 3,
			fillOpacity: 0.6
		};
		var geojsonAreaOptions = {
			radius: 8,
			fillColor: "#8888ff",
			color: "#33f",
			weight: 1,
			opacity: 1,
			fillOpacity: 0.2
		};

		var geoJsonOptions = {
			pointToLayer: function (featureData,latlng) {
				myOptions = geojsonMarkerOptions;
				myOptions.radius = calcCircleSize();
				return new L.CircleMarker(latlng, myOptions);
			},
			onEachFeature: function (feature, layer) {
				layer.bindPopup(feature.properties.popupContent);
			}
		}

		var geojsonLayer = new L.GeoJSON(null, geoJsonOptions);
		map.addLayer(geojsonLayer);

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
			center = map.getCenter();

			$.ajax({
			  url: "fetch-poi.php" + '?lat=' + center.lat + '&lon=' + center.lng,
			  beforeSend: function ( xhr ) {
				xhr.overrideMimeType("text/plain; charset=x-user-defined");
			  }
			}).done(function ( data ) {
				geojsonLayer.clearLayers();
				res = jQuery.parseJSON(data);
				res.forEach( function(value) {
					geojsonLayer.addData(value);

					point = null;
					classNamePrefix = '';
					if (value.properties.classes) {
						classNamePrefix = value.properties.classes + ' ';
					}
					if (value.geometry.type == 'Point') {
						var myIcon = L.divIcon({html: value.properties.name, iconSize: 100, className: classNamePrefix + 'markerName'});
						point = [ value.geometry.coordinates[1], value.geometry.coordinates[0] ];
					} else if (value.geometry.type == 'Polygon') {
						var myIcon = L.divIcon({html: value.properties.name, iconSize: 640, className: classNamePrefix + 'markerName'});
						point = calcCentre( value.geometry.coordinates[0] );
					}
					if (point) {
						L.marker(point, {icon: myIcon}).addTo(geojsonLayer);
						if (false) {
							L.polyline([center, point], {color: 'red'}).addTo(geojsonLayer);
						}
					}
				} );
				geojsonLayer.addLayer(new L.CircleMarker(center, { color: '#f00', radius: 5, fillOpacity: 1 } ) );
			});
		}
	</script>
</body>
</html>
