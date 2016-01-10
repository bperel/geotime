function showTerritoriesForYear(year) {
	ajaxPost(
		{ getTerritoriesForYear: true, year: year },
		function(error, data) {
			svg.append("path")
				.datum({type: "LineString", coordinates:
					data.territory.polygon[0][0] // points in decimal degrees
				})
				.attr("d", d3.geo.path().projection(projection))
				.style({'fill': '#B10000', 'fill-opacity': 0.3})
				.style({'stroke-width': 1, 'stroke': '#B10000', 'stroke-linejoin': 'round'});
		}
	);
}