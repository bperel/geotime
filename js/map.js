var width = 960,
	height = 1160;

var projection = d3.geo.equirectangular()
	.scale((width + 1) / 2 / Math.PI)
	.precision(.01);

var path = d3.geo.path()
	.projection(projection);

var svg = d3.select("body").append("svg")
	.attr("width", width)
	.attr("height", height);

d3.json("data/external/ne_110m_admin_0_countries.json", function(error, world) {
	svg.selectAll(".subunit")
		.data(world.features)
		.enter().append("path")
		.attr("class", function(d) {
			return "subunit-boundary subunit " + d.properties.adm0_a3;
		})
		.attr("d", path);

	svg.append("path")
		.datum(world.features)
		.attr("d", path)
		.attr("class", "subunit-boundary");

	svg.selectAll(".subunit-label")
		.data(world.features)
		.enter().append("text")
		.attr("class", function(d) { return "subunit-label " + d.properties.adm0_a3; })
		.attr("transform", function(d) { return "translate(" + path.centroid(d) + ")"; })
		.attr("dy", ".35em")
		.text(function(d) { return d.properties.adm0_a3; });

});