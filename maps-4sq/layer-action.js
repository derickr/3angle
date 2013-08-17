<?php
$s = '';
if (array_key_exists( 'gc_segments', $_GET ) ) {
	$segments = (int) $_GET['gc_segments'];
	$s = " + '&segments={$segments}'";
}
?>
if (map.hasLayer( foursquareLayer )) {
	$.ajax({
		url: "maps-4sq/fetch-poi.php" + '?n=' + bounds.getNorthEast().lat + '&e=' + bounds.getNorthEast().lng + '&s=' + bounds.getSouthWest().lat + '&w=' + bounds.getSouthWest().lng<?php echo $s; ?>,
		beforeSend: function ( xhr ) {
			xhr.overrideMimeType("text/plain; charset=x-user-defined");
		}
	}).done(function ( data ) {
		foursquareLayer.clearLayers();

		var clusterFoursquareLayer = new L.MarkerClusterGroup({maxClusterRadius: 20, singleMarkerMode: 1});
		
		res = jQuery.parseJSON(data);
		res.forEach( function(value) {
			if (value.geometry.type == 'Point') {
				point = [ value.geometry.coordinates[1], value.geometry.coordinates[0] ];

				var marker = L.marker(point);
				marker.addTo(clusterFoursquareLayer);
				marker.bindPopup(value.properties.popupContent, { maxWidth:1000 });
			}
		} );

		foursquareLayer.addLayer(clusterFoursquareLayer);
	});
}
