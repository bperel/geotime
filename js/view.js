function showTerritoriesForYear(year) {
	ajaxPost(
		{ getTerritoriesForYear: true, year: year },
		function(error, data) {
			var territories = svg.selectAll("path.locatedTerritory")
				.data(data.territories);

			territories
				.enter()
					.append("path")
						.attr("class", "locatedTerritory")
						.style({'fill': '#B10000', 'fill-opacity': 0.3})
						.style({'stroke-width': 1, 'stroke': '#B10000', 'stroke-linejoin': 'round'});

			territories
				.datum(function(d) { return {type: "LineString", coordinates: d.polygon[0][0] };})
				.attr("d", d3.geo.path().projection(projection))
				.attr("id", function(d) { return d.name; });


			territories.exit().remove();
		}
	);
}