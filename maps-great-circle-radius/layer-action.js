<?php
$s = '';
if (array_key_exists( 's', $_GET ) ) {
	$size = (float) $_GET['s'];
	$s .= " + '&size={$size}'";
}
if (array_key_exists( 'plat', $_GET ) ) {
	$plat = (float) $_GET['plat'];
	$s .= " + '&plat={$plat}'";
}
if (array_key_exists( 'plng', $_GET ) ) {
	$plng = (float) $_GET['plng'];
	$s .= " + '&plng={$plng}'";
}
?>
if (map.hasLayer( gcrLayer )) {
	$.ajax({
		url: "maps-great-circle-radius/fetch-poi.php" + '?n=' + bounds.getNorthEast().lat + '&e=' + bounds.getNorthEast().lng + '&s=' + bounds.getSouthWest().lat + '&w=' + bounds.getSouthWest().lng<?php echo $s; ?>,
		beforeSend: function ( xhr ) {
			xhr.overrideMimeType("text/plain; charset=x-user-defined");
		}
	}).done(function ( data ) {
		gcrLayer.clearLayers();
		res = jQuery.parseJSON(data);
		res.forEach( function(value) {
			gcrLayer.addData(value);
		} );
	});
}
