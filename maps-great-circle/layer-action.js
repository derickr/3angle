if (map.hasLayer( gcLayer )) {
	$.ajax({
		url: "maps-great-circle/fetch-poi.php" + '?n=' + bounds.getNorthEast().lat + '&e=' + bounds.getNorthEast().lng + '&s=' + bounds.getSouthWest().lat + '&w=' + bounds.getSouthWest().lng,
	  beforeSend: function ( xhr ) {
		xhr.overrideMimeType("text/plain; charset=x-user-defined");
	  }
	}).done(function ( data ) {
		gcLayer.clearLayers();
		res = jQuery.parseJSON(data);
		res.forEach( function(value) {
			gcLayer.addData(value);
		} );
	});
}
