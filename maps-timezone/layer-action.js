<?php
$s = '';
if (array_key_exists( 'limit', $_GET ) ) {
	$limit = (float) $_GET['limit'];
	$s .= " + '&limit={$limit}'";
}
?>
if (map.hasLayer( timezoneLayer )) {
	$.ajax({
	  url: "maps-timezone/fetch-poi.php" + '?lat=' + center.lat + '&lon=' + center.lng<?php echo $s; ?>,
	  beforeSend: function ( xhr ) {
		xhr.overrideMimeType("text/plain; charset=x-user-defined");
	  }
	}).done(function ( data ) {
		timezoneLayer.clearLayers();
		res = jQuery.parseJSON(data);
		res.forEach( function(value) {
			timezoneLayer.addData(value);

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
		} );
		timezoneLayer.addLayer(new L.CircleMarker(center, { color: '#f00', radius: 5, fillOpacity: 1 } ) );
	});
}
