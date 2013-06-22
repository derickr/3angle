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
var flickrLayer = new L.GeoJSON(null, flickrLayerOptions);
