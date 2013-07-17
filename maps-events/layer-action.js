if (map.hasLayer( eventsLayer )) {
	$.ajax({
		url: "maps-events/fetch-poi.php" + '?location=lon:' + center.lng + ',lat:' + center.lat,
		beforeSend: function ( xhr ) {
			xhr.overrideMimeType("text/plain; charset=x-user-defined");
		}
	}).done(function ( data ) {
		eventsLayer.clearLayers();

		var clustereventsLayer = new L.MarkerClusterGroup({maxClusterRadius: 20, singleMarkerMode: 1});
		
		res = jQuery.parseJSON(data);
		res.forEach( function(value) {
			if (value.geometry.type == 'Point') {
				point = [ value.geometry.coordinates[1], value.geometry.coordinates[0] ];

				var marker = L.marker(point);
				marker.addTo(clustereventsLayer);
				marker.bindPopup(value.properties.popupContent, { maxWidth:1000 });
			}
		} );

		eventsLayer.addLayer(clustereventsLayer);
	});
}
