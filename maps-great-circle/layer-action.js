<?php
$s = '';
if (array_key_exists( 'gc_segments', $_GET ) ) {
	$segments = (int) $_GET['gc_segments'];
	$s .= " + '&segments={$segments}'";
}
if (array_key_exists( 's', $_GET ) ) {
	$size = (float) $_GET['s'];
	$s .= " + '&size={$size}'";
}
?>
if (map.hasLayer( gcLayer )) {
	$.ajax({
		url: "maps-great-circle/fetch-poi.php" + '?n=' + bounds.getNorthEast().lat + '&e=' + bounds.getNorthEast().lng + '&s=' + bounds.getSouthWest().lat + '&w=' + bounds.getSouthWest().lng<?php echo $s; ?>,
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
