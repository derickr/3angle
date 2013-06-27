if (map.hasLayer( fivepubsLayer )) {
	$.ajax({
		url: "maps-5pubs/fetch-poi.php" + '?lat=' + center.lat + '&lon=' + center.lng,
		beforeSend: function ( xhr ) {
			xhr.overrideMimeType("text/plain; charset=x-user-defined");
		}
	}).done(function ( data ) {
		fivepubsLayer.clearLayers();
		
		res = jQuery.parseJSON(data);
		res.forEach( function(value) {
			fivepubsLayer.addData(value);

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
				L.marker(point, {icon: myIcon}).addTo(fivepubsLayer);
				L.polyline([center, point], {color: 'red'}).addTo(fivepubsLayer);
			}
			fivepubsLayer.addLayer(new L.CircleMarker(center, { color: '#f00', radius: 3, fillOpacity: 1 } ) );
		} );
	});
}
