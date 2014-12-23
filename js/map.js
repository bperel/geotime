var width = 960;
var mapHeight= 480;
var mapPadding = 200;
var resizeHandleSize = 16;
var maxExternalMapSizePercentage = 80;
var svg;
var hoveredTerritory;
var selectedTerritory;

var projection,
	path,
	zoom;

var svgmap_drag = d3.behavior.drag()
	.origin(function(d) { return d; })
	.on("dragstart", dragstarted)
	.on("drag", dragmove);

var bgSvgmap_drag = d3.behavior.drag()
	.origin(function(d) { return d; })
	.on("dragstart", dragstarted)
	.on("drag", bgMapDragMove);


var svgmap_resize = d3.behavior.drag()
	.on("dragstart", dragresizestarted)
	.on("drag", dragresize);

function applyProjection(name) {
	projection = d3.geo[name]()
		.scale(width / 2 / Math.PI)
		.precision(.01);

	path = d3.geo.path()
		.projection(projection);

	zoom = d3.behavior.zoom()
		.translate(projection.translate())
		.scale(projection.scale())
		.on("zoom", function() {
			projection.scale(d3.event.scale);
			svg.selectAll("path").attr("d", path);
		});

	svg
		.call(bgSvgmap_drag)
		.call(zoom)
		.selectAll('path.subunit').attr("d", path)

}

function dragstarted() {
	d3.event.sourceEvent.stopPropagation();
}

var lambda = d3.scale.linear()
	.domain([0, width])
	.range([-180, 180]);

var phi = d3.scale.linear()
	.domain([0, mapHeight])
	.range([90, -90]);

function bgMapDragMove(d) {
	d.x = (d.x || d3.event.sourceEvent.pageX) + (d3.event ? d3.event.dx : 0);
	d.y = (d.y || d3.event.sourceEvent.pageY) + (d3.event ? d3.event.dy : 0);
	projection.rotate([lambda(d.x), phi(d.y)]);
	svg.selectAll("path").attr("d", path);
}

function dragmove(d) {
	d.x += d3.event ? d3.event.dx : 0;
	d.y += d3.event ? d3.event.dy : 0;
	loadExternalMapPosition([d.x, d.y]);
}

function initMapPlaceHolders(callback) {
	$('#map-placeholders').load('map-placeholders.html', {}, callback);
}

function initMapArea() {
	svg = d3.select("#mapArea").append("svg")
		.attr("width", width)
		.attr("height", mapHeight)
		.attr("id", "map")
		.datum({x: 0, y: 0});

	svg.append("rect")
		.attr("id", "bg")
		.attr("width", width)
		.attr("height", mapHeight);

	var projectionSelection = d3.select('#projectionSelection')
		.on('change', function(d) {
			var selectedOption = this.options[this.selectedIndex];
			applyProjection(d3.select(selectedOption).datum().name);
		});

	projectionSelection.selectAll('option')
		.data([
			{ name: 'mercator' },
			{ name: 'equirectangular' },
			{ name: 'orthographic' }
		])
		.enter().append('option')
		.text(function(d) { return d.name; });
}

function showBgMap(id, data, error) {
	if (error) {
		console.error(error);
	}
	else {
		svg.append("g")
			.attr("id", id)
			.selectAll(".subunit")
			.data(data.features)
			.enter()
				.append("path")
				.attr("class", function (d) {
					return "subunit-boundary subunit " + d.properties.adm0_a3;
				});
		applyProjection('mercator');
	}
}

function getAndShowBgMap(id, filePath) {
	d3.json(filePath, function(error, world) {
		showBgMap(id, world);
	});
}

var svgMap = null;
var isLoading = false;

function initExternalSvgMap(mapFileName) {
	$('#externalSvg').remove();
	if (svgMap) {
		svgMap = null;
	}
	isLoading = false;

	initHelper(mapFileName, helperStepsData);
}

function loadTerritoryMap() {
	if (!isLoading) {
		isLoading = true;
		ajaxPost(
			{ getSvg: 1 },
			function(error, incompleteMapInfo) {
				if (!!incompleteMapInfo) {
					var mapFileName = incompleteMapInfo.fileName;
					if (mapFileName) {
						if (!svgMap || svgMap.datum().fileName !== mapFileName) {
							initExternalSvgMap(mapFileName);
							d3.xml("cache/svg/" + mapFileName, "image/svg+xml", function (xml) {
								svgMap = d3.select(d3.select("#mapArea").node().appendChild(document.importNode(xml.documentElement, true)))
									.attr("name", mapFileName)
									.attr("id", "externalSvg")
									.classed("externalSvg", true)
									.attr("preserveAspectRatio", "xMaxYMax meet");

								svgMap
									.datum({
										id: incompleteMapInfo.id,
										fileName: incompleteMapInfo.fileName,
										x: 0,
										y: 0,
										width: parseInt(svgMap.attr("width")),
										height: parseInt(svgMap.attr("height"))
									});

								if (!svgMap.attr("viewBox")) {
									svgMap.attr("viewBox", function (d) {
										return "0 0 " + d.width + " " + d.height;
									});
								}

								dragmove.call(svgMap.node(), svgMap.datum());

								activateHelperNextStep();

								if (incompleteMapInfo.position) {
									var projectedLeftTop = projection(incompleteMapInfo.position[0]);
									var projectedRightBottom = projection(incompleteMapInfo.position[1]);
									loadExternalMapPosition(projectedLeftTop);
									resizeExternalMap(projectedRightBottom[0]-projectedLeftTop[0], projectedRightBottom[1]-projectedLeftTop[1]);
								}
								else {
									resizeExternalMap();
								}
							});
						}
					}
				}
				isLoading = false;
			}
		);
	}
}

function validateTerritory(data) {
	ajaxPost(
		{
			addTerritory: 1,
			mapId: data.map.id,
			mapProjection: data.map.projection,
			mapPosition: data.map.position,
			territoryName: data.territory.name,
			territoryPeriodStart: data.territory.period.start,
			territoryPeriodEnd: data.territory.period.end,
			xpath: data.territory.xpath,
			coordinates: data.territory.coordinates
		},
		function(error) {
			if (error) {
				alert(error);
			}
			else {
				location.reload();
			}
		}
	);
}

function initTerritoryAutocomplete() {
	autocomplete(d3.select('#territoryName').node())
		.dataField("name")
		.width(960)
		.height(500)
		.render();
}

function loadExternalMapPosition(projectedLeftTop) {
	svgMap.datum(function(d) {
		d.x = projectedLeftTop[0];
		d.y = projectedLeftTop[1];
		return d;
	});
	d3.selectAll("#externalSvg, #resizeHandle")
		.style("margin-left", projectedLeftTop[0]+"px")
		.style("margin-top",+ projectedLeftTop[1] +"px");
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
		.style("left", (mapPadding + width  - resizeHandleSize)+"px")
		.style("top",  (             height - resizeHandleSize)+"px");
}

function onTerritoryMouseover() {
	hoveredTerritory = d3.select(this);
	hoveredTerritory.classed("hovered", true);
	updateTerritoryId();
}

function onTerritoryMouseout() {
	hoveredTerritory.classed("hovered", false);
	hoveredTerritory = null;
	updateTerritoryId();
}

function onHoveredTerritoryClick() {
	var hoveredTerritoryIsSelected =
		hoveredTerritory && selectedTerritory
	 && hoveredTerritory.node() === selectedTerritory.node();

	if (selectedTerritory) {
		selectedTerritory.classed("selected", false);
	}

	if (hoveredTerritoryIsSelected) {
		selectedTerritory = null;
	}
	else {
		selectedTerritory = hoveredTerritory;
		selectedTerritory.classed("selected", true);
	}
	updateTerritoryId();
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