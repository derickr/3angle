var gclMarkerOptions = {
	radius: 12,
	fillColor: "#8888ff",
	color: "#33f",
	weight: 1,
	opacity: 1,
	fillOpacity: 0.6
};
var gclLineOptions = {
	fillColor: "#8888ff",
	color: "#f33",
	weight: 3,
	opacity: 1,
	width: 3,
	fillOpacity: 0.6
};

var gclOptions = {
	pointToLayer: function (featureData,latlng) {
		myOptions = gclMarkerOptions;
		myOptions.radius = calcCircleSize();
		return new L.CircleMarker(latlng, myOptions);
	},
	onEachFeature: function (feature, layer) {
		if (feature.geometry.type == 'LineString') {
			layer.setStyle(gclLineOptions);
		}
		layer.bindPopup(feature.properties.popupContent);
	}
}

var gclLayer = new L.GeoJSON(null, gclOptions);
