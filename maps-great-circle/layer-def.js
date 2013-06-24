var gcMarkerOptions = {
	radius: 12,
	fillColor: "#8888ff",
	color: "#33f",
	weight: 1,
	opacity: 1,
	fillOpacity: 0.6
};
var gcLineOptions = {
	fillColor: "#8888ff",
	color: "#33f",
	weight: 3,
	opacity: 1,
	width: 3,
	fillOpacity: 0.6
};
var gcAreaOptions = {
	radius: 8,
	fillColor: "#8888ff",
	color: "#33f",
	weight: 1,
	opacity: 1,
	fillOpacity: 0.2
};

var gcOptions = {
	pointToLayer: function (featureData,latlng) {
		myOptions = gcMarkerOptions;
		myOptions.radius = calcCircleSize();
		return new L.CircleMarker(latlng, myOptions);
	},
	onEachFeature: function (feature, layer) {
		layer.bindPopup(feature.properties.popupContent);
	}
}

var gcLayer = new L.GeoJSON(null, gcOptions);
