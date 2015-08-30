var width = 960;
var mapHeight= 480;
var mapPadding = 200;
var resizeHandleSize = 16;
var maxExternalMapSizePercentage = 80;
var svg;
var markersSvg = d3.selectAll('nothing');
var hoveredTerritory;
var selectedTerritory;

var projectionSelection;
var mapSelection;
var dragAction;
var dragMode = 'pan';

var bgMapDragState;

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

	if (svg) {
		applyCurrentProjection();
	}
}

function applyCurrentProjection() {
	path.projection(projection);
	zoom.scale(projection.scale());

	drawPaths();
}

function drawPaths() {
	svg.selectAll('path.subunit').attr("d", path);
	d3.select('#projectionCenter').text(projection.center().map(function(val) { return parseInt(val*10)/10; }));
	d3.select('#projectionRotation').text(projection.rotate().map(function(val) { return parseInt(val*10)/10; }));

	if (markersSvg.size() > 0) {
		markersSvg.repositionCalibrationMarkers('bgMap', true);
	}
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
	d.x += d3.event ? d3.event.dx || 0 : 0;
	d.y += d3.event ? d3.event.dy || 0 : 0;
	loadExternalMapPosition(d);
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

		svg
			.call(bgSvgmap_drag)
			.call(zoom);
		applyProjection(getSelectedProjection());
	}
}

function getAndShowBgMap(id, filePath, callback) {
	callback = callback || function() {};
	d3.json(filePath, function(error, world) {
		showBgMap(id, world);
		callback();
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
}

function loadMaps() {
	ajaxPost(
		{ getMaps: 1 },
		function(error, maps) {
			maps.unshift({fileName: null});

			mapSelection = d3.select('#maps');
			mapSelection.selectAll('option')
				.data(maps)
				.enter().append('option')
				.text(function (d) {
					return d.fileName || 'Select a map';
				});
		}
	);
}

function loadTerritoryMapFromSvgElement(mapFileName, mapInfo) {
	svgMap
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

function loadTerritoryMapData(fileName, mapInfo, contentFromFileSystem, callback) {
	var mapFileName = fileName;
	if (mapFileName) {
		if (!svgMap || svgMap.datum().fileName !== mapFileName) {
			initExternalSvgMap(mapFileName);
			if (!!contentFromFileSystem) {
				var svgWrapper = document.createElement('div');
				svgWrapper.innerHTML = contentFromFileSystem;
				var mapArea = d3.select("#mapArea");
				mapArea.node().appendChild(svgWrapper);
				svgMap = mapArea.select('svg');
				loadTerritoryMapFromSvgElement(mapFileName, mapInfo);
				return callback(mapInfo);
			}
			else {
				d3.xml("cache/svg/" + mapFileName, "image/svg+xml", function (svgDocument) {
					svgMap = d3.select(d3.select("#mapArea").node().appendChild(document.importNode(svgDocument.documentElement, true)));
					loadTerritoryMapFromSvgElement(mapFileName, mapInfo);
					callback(mapInfo);
				});
			}
        }
    }
}

function loadTerritoryMap(noUi, fileName) {
	if (!isLoading) {
		isLoading = true;
		ajaxPost(
			{ getSvg: 1, fileName: fileName },
			function(error, incompleteMapInfo) {
				if (!!incompleteMapInfo) {
					loadTerritoryMapData(incompleteMapInfo.fileName, incompleteMapInfo, false, noUi ? function() {} : loadUIConfig);
				}
				isLoading = false;
			}
		);
	}
}

function initProjectionSelect(options) {
	projectionSelection = d3.select('#projectionSelection');

	projectionSelection.selectAll('option')
		.data(options)
		.enter().append('option')
		.text(function (d) {
			return d.name;
		});
}

function loadUI() {
	addCalibrationDefsMarkers();

	initProjectionSelect([
		{name: 'mercator'},
		{name: 'equirectangular'},
		{name: 'orthographic'}
	]);

	projectionSelection
		.on('change', function () {
			applyProjection(getSelectedProjection(), projection.center(), projection.scale(), projection.rotate());
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

function loadUIConfig(mapInfo) {
	displaySelectedProjection(mapInfo.projection);

	if (mapInfo.territories) {
		locatedTerritories = mapInfo.territories.filter(function (d) {
			return d.referencedTerritory && d.area;
		});
	}

	if (mapInfo.center && mapInfo.center.length) {
		initHelper(mapInfo.fileName, helperStepsData, 'territoryIdentification');
		activateHelperNextStep(true);
	}
	else {
		initHelper(mapInfo.fileName, helperStepsData, 'mapLocation');
		activateHelperNextStep();
	}
	calibrationPoints = [];
	if (mapInfo.calibrationPoints) {
		for (var i = 0; i < mapInfo.calibrationPoints.length; i++) {
			var calibrationPoint = mapInfo.calibrationPoints[i];
			addCalibrationMarker("fgMap", calibrationPoint.fgPoint);
			addCalibrationMarker("bgMap", calibrationPoint.bgPoint);
		}
	}
	showCalibrationPoints();
	markersSvg.repositionCalibrationMarkers();
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
				alert('Done');
			}
		}
	);
}

function getExternalMapOffsetToCenter() {
    return {
        x: (width - svgMap.styleIntWithoutPx("width")) / 2,
        y: (mapHeight - svgMap.styleIntWithoutPx("height")) / 2
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

function resizeExternalMap(forcedWidth, forcedHeight) {
	var externalMapWidth  = parseInt(svgMap.attr("width" ));
	var externalMapHeight = parseInt(svgMap.attr("height"));
	if (forcedWidth) {
		var externalMapOriginalRatio = externalMapWidth / externalMapHeight;
		var externalMapCurrentRatio = forcedWidth / forcedHeight;
		if (externalMapCurrentRatio > externalMapOriginalRatio) {
			forcedWidth = forcedHeight * externalMapOriginalRatio;
		}
		else if (externalMapCurrentRatio < externalMapOriginalRatio) {
			forcedHeight = forcedWidth / externalMapOriginalRatio;
		}
	}
	else { // Auto fit
		var bgMapWidth  = width;
		var bgMapHeight = mapHeight;
		var widthRatio = bgMapWidth / externalMapWidth;
		var heightRatio = bgMapHeight / externalMapHeight;
		if (widthRatio < 1 || heightRatio < 1) {
			if (widthRatio < heightRatio) {
				forcedWidth = bgMapWidth * (maxExternalMapSizePercentage / 100);
				forcedHeight = externalMapHeight / (externalMapWidth / forcedWidth);
			}
			else {
				forcedHeight = bgMapHeight * (maxExternalMapSizePercentage / 100);
				forcedWidth = externalMapWidth / (externalMapHeight / forcedHeight);
			}
		}
		else {
			forcedWidth = svgMap.datum().width;
			forcedHeight = svgMap.datum().height;
		}
	}

	svgMap
		.style("width",  forcedWidth +"px")
		.style("height", forcedHeight+"px")
		.datum(function(d) {
			d.width = forcedWidth;
			d.height = forcedHeight;
			return d;
		});

	return { width: forcedWidth, height: forcedHeight };
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

	var widthHeight = resizeExternalMap(newWidth, newHeight);

	resizeHandle
		.style("left", (mapPadding + widthHeight.width  - resizeHandleSize)+"px")
		.style("top",  (             widthHeight.height - resizeHandleSize)+"px");
}

d3.selection.prototype.mapOffset = function() {
	return {
		x: this.styleIntWithoutPx("margin-left") + mapPadding,
		y: this.styleIntWithoutPx("margin-top")
	};
};