function showTerritoriesForYear(year) {
	ajaxPost(
		{ getTerritoriesForYear: true, year: year },
		function(error, data) {
			svg.selectAll("path.locatedTerritory")
				.data(data.territories)
					.enter()
					.append("path")
						.datum(function(d) { return {type: "LineString", coordinates: d.polygon[0][0] };})
						.attr("class", "locatedTerritory")
						.attr("d", d3.geo.path().projection(projection))
						.style({'fill': '#B10000', 'fill-opacity': 0.3})
						.style({'stroke-width': 1, 'stroke': '#B10000', 'stroke-linejoin': 'round'});
		}
	);
}