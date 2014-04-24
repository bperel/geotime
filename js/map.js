var width = 960;
var mapHeight= 480;
var resizeHandleSize = 16;

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

		initExternalSvgMap();
	});
}

var svgMap = null;
var isLoading;

function initExternalSvgMap() {
	$('#externalSvg, #resizeHandle').remove();
	if (svgMap) {
		svgMap = null;
	}
	isLoading = false;
	helper.classed("hidden", true);
}

function loadExternalSvgForYear(year) {
	if (!isLoading) {
		isLoading = true;
		d3.json("gateway.php?getSvg&year="+year+"&ignored="+slider.datum().ignoredMaps.join(",")+"", function(error, incompleteMapInfo) {
			var mapFileName = incompleteMapInfo.fileName;
			if (mapFileName) {
				if (!svgMap || svgMap.datum().fileName !== mapFileName) {
					initExternalSvgMap();

					d3.xml("cache/svg/"+mapFileName, "image/svg+xml", function(xml) {
						svgMap = d3.select(d3.select("#mapArea").node().appendChild(document.importNode(xml.documentElement, true)))
							.attr("name", mapFileName)
							.attr("id", "externalSvg")
							.classed("externalSvg", true);

						svgMap
							.datum({
								id: incompleteMapInfo.id,
								fileName: incompleteMapInfo.fileName,
								x: 0,
								y: 0,
								width:  parseInt(svgMap.attr("width")),
								height: parseInt(svgMap.attr("height"))
							});

						dragmove.call(svgMap.node(), svgMap.datum());

						d3.select("#mapArea")
							.append("svg")
								.attr("id", "resizeHandle")
								.attr("width",  resizeHandleSize)
								.attr("height", resizeHandleSize)
								.call(svgmap_resize)
								.append("rect")
									.attr("x", 0)
									.attr("y", 0)
									.attr("width",  resizeHandleSize)
									.attr("height", resizeHandleSize);

						dragresize.call(d3.select('#resizeHandle').node());

						initHelper();
						activateHelperNextStep();

						isLoading = false;
					});
				}
			}
			else {
				initExternalSvgMap();
			}
		});
	}
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
	svgMap.datum().width  += d3.event ? d3.event.dx : 0;
	svgMap.datum().height += d3.event ? d3.event.dy : 0;

	d3.select("#externalSvg")
		.style("width",  svgMap.datum().width +"px")
		.style("height", svgMap.datum().height+"px");

	d3.select(this)
		.style("left", (200 + svgMap.datum().width  - resizeHandleSize)+"px")
		.style("top",  (      svgMap.datum().height - resizeHandleSize)+"px");
}