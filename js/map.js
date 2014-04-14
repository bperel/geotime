var width = 960;
var mapHeight= 480;

var projection = d3.geo.mercator()
	.scale((width + 1) / 2 / Math.PI)
	.precision(.01);

var path = d3.geo.path()
	.projection(projection);

var zoom = d3.behavior.zoom()
	.translate(projection.translate())
	.scale(projection.scale())
	.on("zoom", function() {
		projection.translate(d3.event.translate).scale(d3.event.scale);
		svg.selectAll("path").attr("d", path);
	});

var svg = d3.select("#mapArea").append("svg")
	.attr("width", width)
	.attr("height", mapHeight)
	.attr("id", "map")
	.call(zoom);

svg.append("rect")
	.attr("id", "bg")
	.attr("width", width)
	.attr("height", mapHeight);

showBgMap("backgroundMap", "data/external/ne_110m_coastline.json");

var svgmap_drag = d3.behavior.drag()
	.origin(function(d) { return d; })
	.on("dragstart", dragstarted)
	.on("drag", dragmove);

var svgmap_resize = d3.behavior.drag()
	.on("dragstart", dragresizestarted)
	.on("drag", dragresize);

function dragstarted() {
	d3.event.sourceEvent.stopPropagation();
}

function dragmove(d) {
	d.x += d3.event ? d3.event.dx : 0;
	d.y += d3.event ? d3.event.dy : 0;
	d3.selectAll("#externalSvg, #resizeHandle")
		.style("margin-left", d.x+"px")
		.style("margin-top",+ d.y+"px");
}

function showBgMap(id, filePath) {
		d3.json(filePath, function(error, world) {

		svg.append("g")
			.attr("id", id)
				.selectAll(".subunit")
			.data(world.features)
			.enter()
				.append("path")
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

function dragresizestarted() {
	d3.event.sourceEvent.stopPropagation();
}

function dragresize(){
	svgMap.datum().width += d3.event.dx;
	svgMap.datum().height += d3.event.dy;

	d3.select("#externalSvg")
		.style("width", svgMap.datum().width+"px")
		.style("height", svgMap.datum().height+"px");

	d3.select("#resizeHandle")
		.style("left", (200 + svgMap.datum().width - 16)+"px")
		.style("top",  (svgMap.datum().height - 16 )+"px");
}