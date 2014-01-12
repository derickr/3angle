var postboxesMarkerOptions = {
	radius: 12,
	fillColor: "#8888ff",
	color: "#33f",
	weight: 1,
	opacity: 1,
	fillOpacity: 0.6
};

var postboxesLayerOptions = {
	pointToLayer: function (featureData,latlng) {
		myOptions = postboxesMarkerOptions;
		myOptions.radius = calcCircleSize();
		return new L.CircleMarker(latlng, myOptions);
	},
	onEachFeature: function (feature, layer) {
		layer.bindPopup(feature.properties.popupContent);
		style = null;

		if (feature.properties.classes) {
			classes = feature.properties.classes.split( ' ' );
			for ( i = 0, l = classes.length; i < l; i++ ) {
				elem = classes[ i ];
				if (elem == 'realcideryes') {
					style = { color: '#0f0', fillColor: '#0f0', fillOpacity: 0.9 };
				}
				if (!style && elem == 'realaleyes') {
					style = { color: '#dd0', fillColor: '#dd0', fillOpacity: 0.9 };
				}
			}
		}

		if (style) {
			layer.setStyle( style );
		}
	}
}
var postboxesLayer = new L.GeoJSON(null, postboxesLayerOptions);
