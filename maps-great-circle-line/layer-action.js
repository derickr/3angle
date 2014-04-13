<?php
$s = '"lon1=' . $_GET['lon1'] . '&lat1=' . $_GET['lat1'] . '&lon2=' . $_GET['lon2'] . '&lat2=' . $_GET['lat2']. '"';
if (array_key_exists( 'gcl_segments', $_GET ) ) {
	$segments = (int) $_GET['gcl_segments'];
	$s .= " + '&segments={$segments}'";
}
?>
if (map.hasLayer( gclLayer )) {
	$.ajax({
		url: "maps-great-circle-line/fetch-poi.php" + '?' + <?php echo $s; ?>,
	  beforeSend: function ( xhr ) {
		xhr.overrideMimeType("text/plain; charset=x-user-defined");
	  }
	}).done(function ( data ) {
		gclLayer.clearLayers();
		res = jQuery.parseJSON(data);
		res.forEach( function(value) {
			gclLayer.addData(value);
		} );
	});
}
