<?php
$s = '';
if (array_key_exists( 'gc_segments', $_GET ) ) {
	$segments = (int) $_GET['gc_segments'];
	$s .= " + '&segments={$segments}'";
}
if (array_key_exists( 'fs', $_GET ) ) {
	$set = (string) $_GET['fs'];
	$s .= " + '&set={$set}'";
}
?>
if (map.hasLayer( flickrLayer )) {
	urlVar = "maps-flickr/fetch-poi.php" + 
		'?n=' + bounds.getNorthEast().lat + 
		'&e=' + bounds.getNorthEast().lng + 
		'&s=' + bounds.getSouthWest().lat + 
		'&w=' + bounds.getSouthWest().lng;
	if ($.urlParam('fs') && $.urlParam('fs') != "all") {
		urlVar += '&set=' + decodeURIComponent($.urlParam('fs'));
	}
	$.ajax({
		url: urlVar,
		beforeSend: function ( xhr ) {
			xhr.overrideMimeType("text/plain; charset=x-user-defined");
		}
	}).done(function ( data ) {
		flickrLayer.clearLayers();

		var clusterFlickrLayer = new L.MarkerClusterGroup({maxClusterRadius: 33, spiderfyDistanceMultiplier: 3});
		
		res = jQuery.parseJSON(data);
		res.forEach( function(value) {
			if (value.geometry.type == 'Point') {
				var myIcon = L.divIcon({html: "<img height='75' width='75' src='" + value.properties.thumbUrl + "'/>", iconSize: new L.Point(75, 75), className: 'flickrImage'});
				point = [ value.geometry.coordinates[1], value.geometry.coordinates[0] ];

				var marker = L.marker(point, {icon: myIcon});
				marker.addTo(clusterFlickrLayer);
				marker.bindPopup(value.properties.popupContent, { maxWidth:1000 });
			}
		} );

		flickrLayer.addLayer(clusterFlickrLayer);
	});
	$.ajax({
		url: "maps-flickr/fetch-sets.php",
		beforeSend: function ( xhr ) {
			xhr.overrideMimeType("text/plain; charset=x-user-defined");
		}
	}).done(function ( data ) {
		$("#flickrInfo").css( 'visibility', 'visible' );
		$("#flickrSet").empty();
		res = jQuery.parseJSON(data);
		res.forEach( function(value) {
			if ($.urlParam('fs') && $.urlParam('fs') == value) {
				$("#flickrSet").append("<option selected='selected' value='" + value + "'>" + value + "</option>");
			} else {
				$("#flickrSet").append("<option value='" + value + "'>" + value + "</option>");
			}
		} );
	});
} else {
	$("#flickrInfo").css( 'visibility', 'hidden' );
}
