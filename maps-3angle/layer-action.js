if (map.hasLayer( threeangleLayer )) {
	$.ajax({
		url: "maps-3angle/fetch-poi.php" + '?lat=' + center.lat + '&lon=' + center.lng,
		beforeSend: function ( xhr ) {
			xhr.overrideMimeType("text/plain; charset=x-user-defined");
		}
	}).done(function ( data ) {
		threeangleLayer.clearLayers();
		
		res = jQuery.parseJSON(data);
		res.forEach( function(value) {
			point = null;
			classNamePrefix = '';
			if (value.properties.classes) {
				classNamePrefix = value.properties.classes + ' ';
			}
			if (value.geometry.type == 'Point') {
				point = [ value.geometry.coordinates[1], value.geometry.coordinates[0] ];
			} else if (value.geometry.type == 'Polygon') {
				point = calcCentre( value.geometry.coordinates[0] );
			}
			if (value.properties.score == 'review') {
				var myIcon = new L.Icon( { iconUrl: 'maps-3angle/images/blue-marker.png' } );
			} else if (value.properties.score > 99) {
				var myIcon = new L.Icon( { iconUrl: 'maps-3angle/images/green-marker.png' } );
			} else if (value.properties.score > 60) {
				var myIcon = new L.Icon( { iconUrl: 'maps-3angle/images/orange-marker.png' } );
			} else {
				var myIcon = new L.Icon( { iconUrl: 'maps-3angle/images/red-marker.png' } );
			}
			var marker = L.marker(point, { icon: myIcon } );
			marker.bindPopup(value.properties.popupContent);
			threeangleLayer.addLayer(marker);
		} );
	});
}
