var width = 960;
var mapHeight= 480;
var resizeHandleSize = 16;
var maxExternalMapSizePercentage = 80;

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

initAutocomplete();

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

var resizeHandle = d3.select('#resizeHandle');
var territoryName = d3.select('#territoryName');
var svgMap = null;
var isLoading = false;

function initExternalSvgMap() {
	$('#externalSvg').remove();
	if (svgMap) {
		svgMap = null;
	}
	isLoading = false;
	helper.classed("hidden", true);
	resizeHandle.classed("hidden", true);
}

function loadExternalSvgForYear(year) {
	if (!isLoading) {
		isLoading = true;
		ajaxPost(
			{ getSvg: 1, year: year, ignored: slider.datum().ignoredMaps.join(",")+"" },
			function(error, incompleteMapInfo) {
				initExternalSvgMap();
				var mapFileName = incompleteMapInfo.fileName;
				if (mapFileName) {
					if (!svgMap || svgMap.datum().fileName !== mapFileName) {
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

							if (!svgMap.attr("viewBox")) {
								svgMap.attr("viewBox",  function(d) { return "0 0 "+ d.width+" "+ d.height; });
							}

							dragmove.call(svgMap.node(), svgMap.datum());

							resizeHandle
								.attr("width",  resizeHandleSize)
								.attr("height", resizeHandleSize)
								.select("rect")
									.attr("width",  resizeHandleSize)
									.attr("height", resizeHandleSize);

							initHelper();
							activateHelperNextStep();

							resizeExternalMap();

							isLoading = false;
						});
					}
				}
			}
		);
	}
}

function initAutocomplete() {
	autocomplete(d3.select('#territoryName')[0][0])
		.dataField("name")
		.width(960)
		.height(500)
		.render();
}

function resizeExternalMap(width, height) {
	if (!width) { // Auto fit
		var bgMapWidth  = parseInt(svg.attr("width" ));
		var bgMapHeight = parseInt(svg.attr("height"));
		var externalMapWidth = svgMap.datum().width;
		var externalMapHeight = svgMap.datum().height;
		var widthRatio = bgMapWidth / externalMapWidth;
		var heightRatio = bgMapHeight / externalMapHeight;
		if (widthRatio < 1 || heightRatio < 1) {
			if (widthRatio < heightRatio) {
				width = bgMapWidth * (maxExternalMapSizePercentage / 100);
				height = externalMapHeight / (externalMapWidth / width);
			}
			else {
				height = bgMapHeight * (maxExternalMapSizePercentage / 100);
				width = externalMapWidth / (externalMapHeight / height);
			}
		}
		else {
			width = svgMap.datum().width;
			height = svgMap.datum().height;
		}
	}

	svgMap
		.style("width",  width +"px")
		.style("height", height+"px")
		.datum(function(d) {
			d.width = width;d.height = height;
			return d;
		});

	resizeHandle
		.style("left", (200 + width  - resizeHandleSize)+"px")
		.style("top",  (      height - resizeHandleSize)+"px");
}

function onTerritoryMouseover() {
	d3.select(this).classed("hovered", true);
}

function onTerritoryMouseout() {
	d3.select(this).classed("hovered", false);
}

function onTerritoryClick() {
	svgMap.selectAll("path.selected").classed("selected", false);
	d3.select(this).classed("selected", true);
	if (helper.datum().activeStep === 2) {
		activateHelperNextStep();
	}
}

function dragresizestarted() {
	d3.event.sourceEvent.stopPropagation();
}

function dragresize(){
	var newWidth = svgMap.datum().width;
	var newHeight = svgMap.datum().height;
	if (d3.event) {
		newWidth += d3.event.dx;
		newHeight += d3.event.dy;
	}

	resizeExternalMap(newWidth, newHeight);
}