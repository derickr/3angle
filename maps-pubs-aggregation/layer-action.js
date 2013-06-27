if (map.hasLayer( pubsaggrLayer )) {
	$.ajax({
		url: "maps-pubs-aggregation/fetch-poi.php" + '?lat=' + center.lat + '&lon=' + center.lng,
		beforeSend: function ( xhr ) {
			xhr.overrideMimeType("text/plain; charset=x-user-defined");
		}
	}).done(function ( data ) {
		pubsaggrLayer.clearLayers();
		
		res = jQuery.parseJSON(data);
		res.forEach( function(value) {
			pubsaggrLayer.addData(value);
			point = null;
			itemClassName = 'markerName';

			if (value.geometry.type == 'Point') {
				var myIcon = L.divIcon({html: value.properties.name, iconSize: 100, className: itemClassName});
				point = [ value.geometry.coordinates[1], value.geometry.coordinates[0] ];
			} else if (value.geometry.type == 'Polygon') {
				var myIcon = L.divIcon({html: value.properties.name, iconSize: 640, className: itemClassName});
				point = calcCentre( value.geometry.coordinates[0] );
			}
			if (point) {
				m = L.marker(point, {icon: myIcon}).addTo(pubsaggrLayer);
			}
			pubsaggrLayer.addLayer(new L.CircleMarker(center, { color: '#f00', radius: 3, fillOpacity: 1 } ) );
		} );
	});
}
