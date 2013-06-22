if (map.hasLayer( flickrLayer )) {
	$.ajax({
	  url: "maps-flickr/fetch-poi.php" + '?lat=' + center.lat + '&lon=' + center.lng,
	  beforeSend: function ( xhr ) {
		xhr.overrideMimeType("text/plain; charset=x-user-defined");
	  }
	}).done(function ( data ) {
		flickrLayer.clearLayers();

		var clusterFlickrLayer = new L.MarkerClusterGroup({maxClusterRadius: 20});
		
		res = jQuery.parseJSON(data);
		res.forEach( function(value) {
			if (value.geometry.type == 'Point') {
				var myIcon = L.divIcon({html: "<img height='50' width='50' src='" + value.properties.thumbUrl + "'/>", iconSize: new L.Point(50, 50), className: 'flickrImage'});
				point = [ value.geometry.coordinates[1], value.geometry.coordinates[0] ];

				var marker = L.marker(point, {icon: myIcon});
				marker.addTo(clusterFlickrLayer);
				marker.bindPopup(value.properties.popupContent, { maxWidth:1000 });
			}
		} );

		flickrLayer.addLayer(clusterFlickrLayer);
	});
}
