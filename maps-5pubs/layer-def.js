var fivepubsMarkerOptions = {
	radius: 12,
	fillColor: "#8888ff",
	color: "#33f",
	weight: 1,
	opacity: 1,
	fillOpacity: 0.6
};

var fivepubsLayerOptions = {
	pointToLayer: function (featureData,latlng) {
		myOptions = fivepubsMarkerOptions;
		myOptions.radius = calcCircleSize();
		return new L.CircleMarker(latlng, myOptions);
	},
	onEachFeature: function (feature, layer) {
		layer.bindPopup(feature.properties.popupContent);
	}
}
var fivepubsLayer = new L.GeoJSON(null, fivepubsLayerOptions);
