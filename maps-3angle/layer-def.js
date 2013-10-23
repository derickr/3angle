var threeangleMarkerOptions = {
	radius: 12,
	fillColor: "#8888ff",
	color: "#f3f",
	weight: 1,
	opacity: 1,
	fillOpacity: 0.6
};

var threeangleLayerOptions = {
/*
	onEachFeature: function (feature, layer) {
		layer.bindPopup(feature.properties.popupContent);
	}
*/
}
var threeangleLayer = new L.GeoJSON(null, threeangleLayerOptions);
