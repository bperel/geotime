var width = 960;

var projection = d3.geo.equirectangular()
	.scale((width + 1) / 2 / Math.PI)
	.precision(.01);

var svg = d3.select("#mapArea").append("svg")
	.attr("width", width)
	.attr("height", mapHeight)
	.attr("id", "map");

showMap("background", "data/external/ne_110m_coastline.json");

var svgmap_drag = d3.behavior.drag()
	.origin(function(d) { return d; })
	.on("dragstart", dragstarted)
	.on("drag", dragmove);

function dragstarted(d) {
	d3.event.sourceEvent.stopPropagation();
}

function dragmove(d, a, b) {
	d.x = (d.x || 0) + d3.event.dx;
	d.y = (d.y || 0) + d3.event.dy;
	d3.select(this).attr("style", "margin-left: "+ d.x+"px; margin-top: "+ d.y+"px");
}

function showMap(id, filePath) {
	d3.json(filePath, function(error, world) {
		var path = d3.geo.path()
			.projection(projection);

		svg.append("g")
			.attr("id", id)
			.selectAll(".subunit")
			.data(world.features)
			.enter().append("path")
			.attr("class", function(d) {
				return "subunit-boundary subunit " + d.properties.adm0_a3;
			})
			.attr("d", path);
	});
}

function onTerritoryMouseover() {
	d3.select(this).classed("hovered", true);
}

function onTerritoryMouseout() {
	d3.select(this).classed("hovered", false);
}