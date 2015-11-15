var widthSuperimposed = 960;
var widthSideBySide = 480;

var width = widthSuperimposed;

var mapHeight= 480;
var maxExternalMapSizePercentage = 80;
var svg;
var markersSvg = d3.selectAll('nothing');

var projections = [
	'mercator',
	'equirectangular',
	'orthographic'
];

var projectionSelection = d3.selectAll('nothing');
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

var bgSvgmap_drag = d3.behavior.drag()
	.origin(function(d) { return d; })
	.on("dragstart", bgMapDragStarted)
	.on("drag", bgMapDragMove);

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

	if (markersSvg.size() > 0 && helper.size() && helper.datum().activeProcess === 'mapLocation') {
		markersSvg.repositionCalibrationMarkers('bgMap');
	}
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

function initMapPlaceHolders(callback) {
	$('#map-placeholders').load('map-placeholders.html', {}, callback);
}

function initBackgroundMap() {

	svg = d3.select("#mapArea").append("svg")
		.attr("id", "map")
		.datum({x: 0, y: 0});

	svg.append("rect")
		.attr("id", "bg");
}

function resizeBackgroundMap(width, height) {
	svg
		.attr("width", width)
		.attr("height", height)
		.select("#bg")
			.attr("width", width)
			.attr("height", height);
}

function getSelectedProjection() {
	return (projectionSelection.size() > 0)
		? d3.select(projectionSelection.node().options[projectionSelection.node().selectedIndex]).datum().name
		: 'mercator';
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

function initExternalSvgMap() {
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

d3.selection.prototype.addStripedPatternAndMaskDefs = function() {
	var defs = this.select('defs');
	if (defs.empty()) {
		defs = this.append('defs');
	}
	defs
		.append('g')
			.attr('id', 'geotime-stripe-pattern')
			.loadTemplate({
				process: 'territoryIdentification',
				name: 	 'striped-pattern-defs',
				noConditionalShow: true
			});
};

function loadTerritoryMapFromSvgElement(mapFileName, mapInfo) {
	svgMap
		.attr("name", mapFileName)
		.attr("id", "externalSvg")
		.classed("externalSvg", true)
		.attr("preserveAspectRatio", "xMinYMin meet")
		.call(
			d3.behavior.drag()
				.origin(function(d) { return d; })
		)
		.addStripedPatternAndMaskDefs();

	var svgMapWidth = svgMap.attrIntWithoutPx("width");
	var svgMapHeight = svgMap.attrIntWithoutPx("height");

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

	resizeExternalMap(svg.attrIntWithoutPx('width'), svg.attrIntWithoutPx('height'));
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
					svgMap = d3.select(d3.select("#mapArea").node().insertBefore(document.importNode(svgDocument.documentElement, true), svg.node()));
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

function loadUI() {
	addCalibrationDefsMarkers();

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
	svgMap.datum(mapInfo);

	if (mapInfo.projection) {
		applyProjection(mapInfo.projection, mapInfo.center, mapInfo.scale, mapInfo.rotation);
		displaySelectedProjection(mapInfo.projection);
	}
	else {
		applyProjection('mercator', 0, 0, [0, 0, 0]);
	}


	if (mapInfo.projection || mapInfo.territories.length) {
		initHelper('territoryIdentification');
	}
	else {
		initHelper('mapLocation');
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
				alert('Done');
			}
		}
	);
}

function getExternalMapOffsetToRight() {
	return {
		x: svg.attrIntWithoutPx('width'),
		y: 0
	};
}

function getExternalMapOffsetToCenter() {
    return {
        x: (width - svgMap.styleIntWithoutPx("width")) / 2,
        y: (mapHeight - svgMap.styleIntWithoutPx("height")) / 2
    };
}

function positionExternalMap(sideBySide) {
	loadExternalMapPosition(
		sideBySide
			? getExternalMapOffsetToRight()
			: getExternalMapOffsetToCenter()
    );
}

function loadExternalMapPosition(projectedLeftTop) {
	svgMap.datum(function(d) {
		d.x = projectedLeftTop.x;
		d.y = projectedLeftTop.y;
		return d;
	});
	svgMap
		.style("margin-left", projectedLeftTop.x+"px")
		.style("margin-top",+ projectedLeftTop.y +"px");

	markersSvg
		.attr("width", projectedLeftTop.x + svgMap.attrIntWithoutPx("width"))
		.selectAll("g.fgMap")
			.attr("transform", "translate("+[projectedLeftTop.x, projectedLeftTop.y].join(" ")+")")
}

function resizeExternalMap(forcedWidth, forcedHeight) {
	var externalMapWidth  = svgMap.attrIntWithoutPx("width");
	var externalMapHeight = svgMap.attrIntWithoutPx("height");
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
		var bgMapWidth  = svg.attrIntWithoutPx("width");
		var bgMapHeight = svg.attrIntWithoutPx("height");
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

d3.selection.prototype.mapOffset = function() {
	return {
		x: this.styleIntWithoutPx("margin-left"),
		y: this.styleIntWithoutPx("margin-top")
	};
};