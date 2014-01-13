if (map.hasLayer( postboxLayer )) {
	$.ajax({
		url: "maps-postbox/fetch-poi.php" + '?lat=' + center.lat + '&lon=' + center.lng,
		beforeSend: function ( xhr ) {
			xhr.overrideMimeType("text/plain; charset=x-user-defined");
		}
	}).done(function ( data ) {
		postboxLayer.clearLayers();
		
		res = jQuery.parseJSON(data);
		res.forEach( function(value) {
			postboxLayer.addData(value);
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
				m = L.marker(point, {icon: myIcon}).addTo(postboxLayer);
			}
//			postboxLayer.addLayer(new L.CircleMarker(center, { color: '#00f', radius: 3, fillOpacity: 1 } ) );
		} );
	});
}
