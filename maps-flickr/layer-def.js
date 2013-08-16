var flickrLayerOptions = {
	pointToLayer: function (featureData,latlng) {
		myOptions = geojsonMarkerOptions;
		myOptions.radius = calcCircleSize();
		return new L.CircleMarker(latlng, myOptions);
	},
	onEachFeature: function (feature, layer) {
		layer.bindPopup(feature.properties.popupContent);
	}
}
function flickrChangeSet(e) {
	var set = ""
	var selected = $("#flickrSet option:selected").each(function () {
		set += $(this).text() + " ";
	});
	window.location.hash = "fs=" + set;
	changeLocation();
}
var flickrLayer = new L.GeoJSON(null, flickrLayerOptions);
$("#flickrSet").attr("onchange", "flickrChangeSet()");
