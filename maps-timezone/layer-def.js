var timezoneMarkerOptions = {
	radius: 12,
	fillColor: "#8888ff",
	color: "#33f",
	weight: 1,
	opacity: 1,
	fillOpacity: 0.6
};
var timezoneLineOptions = {
	fillColor: "#8888ff",
	color: "#33f",
	weight: 3,
	opacity: 1,
	width: 3,
	fillOpacity: 0.6
};
var timezoneAreaOptions = {
	radius: 8,
	fillColor: "#8888ff",
	color: "#33f",
	weight: 1,
	opacity: 1,
	fillOpacity: 0.2
};

var timezoneOptions = {
	pointToLayer: function (featureData,latlng) {
		myOptions = timezoneMarkerOptions;
		myOptions.radius = calcCircleSize();
		return new L.CircleMarker(latlng, myOptions);
	},
	onEachFeature: function (feature, layer) {
		layer.bindPopup(feature.properties.popupContent);
	}
}

var timezoneLayer = new L.GeoJSON(null, timezoneOptions);
