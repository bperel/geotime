var width = 960;
var mapHeight= 480;
var mapPadding = 200;
var resizeHandleSize = 16;
var maxExternalMapSizePercentage = 80;
var svg;
var markersSvg;
var hoveredTerritory;
var selectedTerritory;

var projectionSelection;
var dragAction;
var dragMode = 'pan';

var bgMapDragState;

var calibrationPoints = [];
var locatedTerritories = [];

var projection,
	path = d3.geo.path(),
	zoom = d3.behavior.zoom()
		.on("zoom", function() {
			projection.scale(d3.event.scale);
			drawPaths();
		});

var svgmap_drag = d3.behavior.drag()
	.origin(function(d) { return d; })
	.on("dragstart", dragstarted)
	.on("drag", dragmove);

var bgSvgmap_drag = d3.behavior.drag()
	.origin(function(d) { return d; })
	.on("dragstart", bgMapDragStarted)
	.on("drag", bgMapDragMove);


var svgmap_resize = d3.behavior.drag()
	.on("dragstart", dragresizestarted)
	.on("drag", dragresize);

function applyProjection(name, center, scale, rotation) {
	projection = d3.geo[name]()
		.center(center || [0, 0])
		.scale(scale || width / 2 / Math.PI)
		.rotate(rotation || (projection ? projection.rotate() : [0, 0, 0]))
		.precision(.01);

	applyCurrentProjection();
}

function applyCurrentProjection() {
	path.projection(projection);

	zoom.scale(projection.scale());

	svg
		.call(bgSvgmap_drag)
		.call(zoom);

	drawPaths();
}

function drawPaths() {
	svg.selectAll('path.subunit').attr("d", path);
	d3.select('#projectionCenter').text(projection.center().map(function(val) { return parseInt(val*10)/10; }));
	d3.select('#projectionRotation').text(projection.rotate().map(function(val) { return parseInt(val*10)/10; }));

	repositionCalibrationMarkers();
}

function dragstarted() {
	d3.event.sourceEvent.stopPropagation();
}

function bgMapDragStarted() {
	bgMapDragState = 'inactive';
}

var longLatLimits = [180, 90];

var lambda = d3.scale.linear()
	.domain([0, width])
	.range([-longLatLimits[0], longLatLimits[0]]);

var phi = d3.scale.linear()
	.domain([0, mapHeight])
	.range([longLatLimits[1], -longLatLimits[1]]);

function bgMapDragMove(d) {
	if (d3.event && d3.event.dx && d3.event.dy) {
		bgMapDragState = 'drag';
	}
	if (dragMode === 'pan') {
		var currentCenter = projection.center();
		var newCenter = [
			Math.min(longLatLimits[0], Math.max(-longLatLimits[0], currentCenter[0]-d3.event.dx)),
			Math.min(longLatLimits[1], Math.max(-longLatLimits[1], currentCenter[1]+d3.event.dy))
		];
		projection.center(newCenter);
	}
	else {
		d.x = (d.x || d3.event.sourceEvent.pageX) + (d3.event ? d3.event.dx : 0);
		d.y = (d.y || d3.event.sourceEvent.pageY) + (d3.event ? d3.event.dy : 0);
		projection.rotate([lambda(d.x), phi(d.y)]);
	}
	drawPaths();
}

function dragmove(d) {
	d.x += d3.event ? d3.event.dx : 0;
	d.y += d3.event ? d3.event.dy : 0;
	loadExternalMapPosition(d);
}

function initMapPlaceHolders(callback) {
	$('#map-placeholders').load('map-placeholders.html', {}, callback);
}

function initMapArea() {

	addCalibrationDefsMarkers();

	svg = d3.select("#mapArea").append("svg")
		.attr("width", width)
		.attr("height", mapHeight)
		.attr("id", "map")
		.datum({x: 0, y: 0});

	svg.append("rect")
		.attr("id", "bg")
		.attr("width", width)
		.attr("height", mapHeight);

	projectionSelection = d3.select('#projectionSelection')
		.on('change', function () {
			applyProjection(getSelectedProjection(), projection.center(), projection.scale(), projection.rotate());
		});

	projectionSelection.selectAll('option')
		.data([
			{name: 'mercator'},
			{name: 'equirectangular'},
			{name: 'orthographic'}
		])
		.enter().append('option')
		.text(function (d) {
			return d.name;
		});

	dragAction = d3.select('#dragActionContainer')
		.selectAll('input')
		.data([
			{name: 'pan', text: 'Pan on drag'},
			{name: 'rotate', text: 'Rotate on drag'}
		])
		.enter().append('div').each(function (d) {
			var wrapper = d3.select(this);
			wrapper.append('input')
				.attr('type', 'radio')
				.attr('name', 'dragAction')
				.attr('id', 'dragAction' + d.name)
				.attr('checked', d.name === 'pan' ? 'checked' : null)
				.on('click', function (d) {
					dragMode = d.name;
				});
			wrapper.append("label")
				.attr("for", 'dragAction' + d.name)
				.text(d.text);
		});
}

function getSelectedProjection() {
	return d3.select(projectionSelection.node().options[projectionSelection.node().selectedIndex]).datum().name;
}

function displaySelectedProjection(projectionName) {
	projectionSelection.selectAll('option')
		.attr('selected', function(d) { return d.name === projectionName ? 'selected' : null});
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
				.attr("class", "subunit-boundary subunit");

		applyProjection(getSelectedProjection());
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

function loadTerritoryMapFromSvgElement(svgElement, mapFileName, mapInfo) {
	svgMap = d3.select(d3.select("#mapArea").node().appendChild(document.importNode(svgElement, true)))
		.attr("name", mapFileName)
		.attr("id", "externalSvg")
		.classed("externalSvg", true)
		.attr("preserveAspectRatio", "xMinYMin meet");

	var svgMapWidth = parseInt(svgMap.attr("width"));
	var svgMapHeight = parseInt(svgMap.attr("height"));

	svgMap
		.datum({
			id: mapInfo.id,
			fileName: mapFileName,
			x: 0,
			y: 0,
			width: svgMapWidth,
			height: svgMapHeight
		});

	if (!svgMap.attr("viewBox")) {
		svgMap.attr("viewBox", function (d) {
			return "0 0 " + d.width + " " + d.height;
		});
	}

	resizeExternalMap();
	centerExternalMap();

	if (mapInfo.projection) {
		applyProjection(mapInfo.projection, mapInfo.center, mapInfo.scale, mapInfo.rotation);
	}
}

function loadTerritoryMap(fileName, mapInfo, callback) {
	var mapFileName = fileName;
	if (mapFileName) {
		if (!svgMap || svgMap.datum().fileName !== mapFileName) {
			initExternalSvgMap(mapFileName);
			d3.xml("cache/svg/" + mapFileName, "image/svg+xml", function (svgDocument) {
				loadTerritoryMapFromSvgElement(svgDocument.documentElement, mapFileName, mapInfo);
				callback(mapInfo);
			});
        }
    }
}

function loadRandomTerritoryMap() {
	if (!isLoading) {
		isLoading = true;
		ajaxPost(
			{ getSvg: 1 },
			function(error, incompleteMapInfo) {
				if (!!incompleteMapInfo) {
					loadTerritoryMap(incompleteMapInfo.fileName, incompleteMapInfo, initUI);
				}
				isLoading = false;
			}
		);
	}
}

function initUI(mapInfo) {
	displaySelectedProjection(mapInfo.projection);

	if (mapInfo.territories) {
		locatedTerritories = mapInfo.territories.filter(function (d) {
			return d.referencedTerritory && d.area;
		});
	}

	if (mapInfo.center) {
		helper.datum().activeStep = 2;
		activateHelperNextStep(null, true);
	}
	else {
		activateHelperNextStep();

		if (mapInfo.calibrationPoints) {
			for (var i = 0; i < mapInfo.calibrationPoints.length; i++) {
				var calibrationPoint = mapInfo.calibrationPoints[i];
				addCalibrationMarker("fgMap", calibrationPoint.fgPoint);
				addCalibrationMarker("bgMap", calibrationPoint.bgPoint);
			}
		}
		showCalibrationPoints();
	}
}

function validateMapLocation(mapData) {
    ajaxPost(
        {
            locateMap: 1,
            mapId: mapData.id,
            mapProjection: mapData.projection,
            mapRotation: mapData.rotation,
            mapCenter: mapData.center,
            mapScale: mapData.scale,
            calibrationPoints: mapData.calibrationPoints
        },
        function(error) {
            if (error) {
                alert(error);
            }
        }
    );
}

function validateTerritories(mapId, territoriesData) {
	ajaxPost(
		{
			addTerritories: 1,
			mapId: mapId,
			territories: territoriesData

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

function getExternalMapOffsetToCenter() {
    return {
        x: (svg.styleIntWithoutPx("width") - svgMap.styleIntWithoutPx("width")) / 2,
        y: (svg.styleIntWithoutPx("height") - svgMap.styleIntWithoutPx("height")) / 2
    };
}

function centerExternalMap() {
	loadExternalMapPosition(
        getExternalMapOffsetToCenter()
    );
}

function loadExternalMapPosition(projectedLeftTop) {
	svgMap.datum(function(d) {
		d.x = projectedLeftTop.x;
		d.y = projectedLeftTop.y;
		return d;
	});
	d3.selectAll("#externalSvg, #resizeHandle")
		.style("margin-left", projectedLeftTop.x+"px")
		.style("margin-top",+ projectedLeftTop.y +"px");

	markersSvg.selectAll("g.fgMap")
		.attr("transform", "translate("+[projectedLeftTop.x, projectedLeftTop.y].join(" ")+")");
}

function resizeExternalMap(width, height) {
	var externalMapWidth  = parseInt(svgMap.attr("width" ));
	var externalMapHeight = parseInt(svgMap.attr("height"));
	if (width) {
		var externalMapOriginalRatio = externalMapWidth / externalMapHeight;
		var externalMapCurrentRatio = width / height;
		if (externalMapCurrentRatio > externalMapOriginalRatio) {
			width = height * externalMapOriginalRatio;
		}
		else if (externalMapCurrentRatio < externalMapOriginalRatio) {
			height = width / externalMapOriginalRatio;
		}
	}
	else { // Auto fit
		var bgMapWidth  = parseInt(svg.attr("width" ));
		var bgMapHeight = parseInt(svg.attr("height"));
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
			d.width = width;
			d.height = height;
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
    d3.select('#currentTerritory').classed('hidden', false);
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

d3.selection.prototype.mapOffset = function() {
	return {
		x: this.styleIntWithoutPx("margin-left") + mapPadding,
		y: this.styleIntWithoutPx("margin-top")
	};
};