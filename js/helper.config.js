function loadHelperConfig() {
	helperButtonsData = [
		{
			name: 'done', cssClass: 'helperStepDone', text: 'Done !',
			click: activateHelperNextStep
		},
		{
			name: 'skip', cssClass: 'helperStepSkip', text: 'Skip this step',
			click: activateHelperNextStep
		},
		{
			name: 'cancel', cssClass: 'helperStepCancel', text: 'Switch to another map',
			click: ignoreCurrentMap
		}
	];

	helperStepsData = [
		{
			step: 1, content: ['Select 4 points on the maps.',
							   '<span id="selectedPointsLength"></span>&nbsp;<label for="selectedPointsLength">selected points.</label>'
							  +'<span id="selectedPoints"></span>'],
			onLoad: [enableCalibrationPointSelection],
			onUnload: [disableCalibrationPointSelection],
			dataUpdate: saveMapProjection,
			buttons: ['done', 'skip', 'cancel']
		}, {
			step: 2, content: ['Move the superimposed map so that it corresponds to the background borders.'],
			onLoad: [enableMapDragResize],
			onUnload: [disableMapDragResize],
			dataInit: initMapData,
			dataUpdate: saveMapPosition,
			buttons: ['done', 'skip', 'cancel']
		}, {
			step: 3, content: ['Select with your mouse a country whose name is written on the map or known by you.',
							   'Chosen territory : <span id="territoryId">None</span>'],
			onLoad: [enableTerritorySelection],
			onUnload: [disableTerritorySelection],
			dataUpdate: saveTerritoryPosition, validate: checkSelectedTerritory,
			buttons: ['done', 'skip', 'cancel']
		}, {
			step: 4, content: ['<label for="territoryName">Type in its name</label>',
							   '<input type="text" id="territoryName" />'],
			onLoad: [initTerritoryAutocomplete],
			dataUpdate: saveTerritoryName,
			buttons: ['done', 'skip', 'cancel']
		}, {
			step: 5, content: ['During what period did this territory have these borders ?<br />',
							   '<label for="territoryPeriodStart">From </label><input type="number" id="territoryPeriodStart" />'
							 + '<label for="territoryPeriodEnd"  > to  </label><input type="number" id="territoryPeriodEnd" />'],
			dataUpdate: saveTerritoryPeriod,
			buttons: ['done', 'cancel']
		}
	];
}// Step 1
function enableCalibrationPointSelection() {
	svg.on('click', function() {
		if (bgMapDragState === 'inactive') {
			var mapOffsetLeft = svg.styleIntWithoutPx("margin-left") + mapPadding;
			var mapOffsetTop = svg.styleIntWithoutPx("margin-top");
			var coordinates = projection.invert([d3.event.x - mapOffsetLeft, d3.event.y - mapOffsetTop]);
			saveCalibrationPoint('bgMap', {lng: coordinates[0], lat: coordinates[1]});
		}
	});

	svgMap
		.on('click', function() {
			if (!d3.event.defaultPrevented) {
				var mapOffsetLeft = svgMap.styleIntWithoutPx("margin-left") + mapPadding;
				var mapOffsetTop = svgMap.styleIntWithoutPx("margin-top");
				saveCalibrationPoint('fgMap', {x: d3.event.x - mapOffsetLeft, y: d3.event.y - mapOffsetTop});
			}
		})
		.call(svgmap_drag);
}

function disableCalibrationPointSelection() {
	svg.on('click', null);
	svgMap.on('click', null);
}

function saveCalibrationPoint(mapType, point) {
	var index = 0;
	while (calibrationPoints[index] && calibrationPoints[index][mapType]) {
		index++;
	}
	calibrationPoints[index] = calibrationPoints[index] || {};
	calibrationPoints[index][mapType] = point;
	var pointsInfo = '';
	for (var i = 0; i <calibrationPoints.length; i++) {
		pointsInfo += '<br />Point '+i+' : ';
		if (calibrationPoints[i].bgMap) {
			pointsInfo += 'bg : '+JSON.stringify(calibrationPoints[i].bgMap);
		}
		if (calibrationPoints[i].fgMap) {
			pointsInfo += 'fg : '+JSON.stringify(calibrationPoints[i].fgMap);
		}
	}
	d3.select('#selectedPoints').html(pointsInfo);
	d3.select('#selectedPointsLength').text(calibrationPoints.length);
}


function saveMapProjection() {

	var closestPointIndex = getClosestPointToCenter();
	var centerCoords = [-calibrationPoints[closestPointIndex].bgMap.lng, -calibrationPoints[closestPointIndex].bgMap.lat];

	applyProjection(getSelectedProjection(), 85*getProjectedFgBgRatio(), centerCoords);
	loadExternalMapPosition([
		(svg.styleIntWithoutPx("width") - svgMap.styleIntWithoutPx("width")) / 2,
		(svg.styleIntWithoutPx("height")- svgMap.styleIntWithoutPx("height"))/ 2
	]);

	return function(d) {
		d.map = {
			id: svgMap.datum().id,
			position: centerCoords,
			projection: getSelectedProjection()
		};
		return d;
	};
}

function getProjectedFgBgRatio() {
	return Math.abs(calibrationPoints[0].fgMap.x - calibrationPoints[1].fgMap.x) / Math.abs(calibrationPoints[0].bgMap.lng - calibrationPoints[1].bgMap.lng) ;
}

function getClosestPointToCenter() {
	var width  = svgMap.styleIntWithoutPx("width"),
		height = svgMap.styleIntWithoutPx("height"),
		minDistance = null,
		minDistancePointIndex = null;

	var center = {x: width/2, y: height/2};

	for (var i = 0; i <calibrationPoints.length; i++) {
		var distance = Math.sqrt(Math.pow(calibrationPoints[i].fgMap.x - center.x, 2) + Math.pow(calibrationPoints[i].fgMap.y - center.y, 2));
		if (minDistance === null || distance < minDistance) {
			minDistance = distance;
			minDistancePointIndex = i;
		}
	}

	return minDistancePointIndex;
}


// Step 2
function enableMapDragResize() {
	svgMap.call(svgmap_drag);
	resizeHandle
		.call(svgmap_resize)
		.classed("hidden", false);
}

function disableMapDragResize() {
	svgMap.on('mousedown.drag', null);
	resizeHandle
		.on('mousedown.drag', null)
		.classed("hidden", true);
}

function initMapData() {
	return function(d) {
		d.map = {
			id: svgMap.datum().id
		};
		return d;
	};
}

function saveMapPosition() {
	var left   = svgMap.styleIntWithoutPx("margin-left"),
		top    = svgMap.styleIntWithoutPx("margin-top"),
		width  = svgMap.styleIntWithoutPx("width"),
		height = svgMap.styleIntWithoutPx("height");
	var pos = [
		projection.invert([left,        top]),
		projection.invert([left+width,  top+height])
	];

	return function(d) {
		d.map = { position: pos };
		return d;
	};
}

// Step 3
function enableTerritorySelection() {
	territoryId = d3.select('#territoryId');

	svgMap
		.selectAll("path")
		.on("mouseover", onTerritoryMouseover)
		.on("mouseout",  onTerritoryMouseout)
		.on("click",     onHoveredTerritoryClick);
}

function disableTerritorySelection() {
	svgMap
		.selectAll("path")
		.on("mouseover", null)
		.on("mouseout",  null)
		.on("click",     null);
}

function updateTerritoryId() {
	var id;
	if (hoveredTerritory) {
		id = hoveredTerritory.attr('id');
	}
	else if (selectedTerritory) {
		id = selectedTerritory.attr('id');
	}
	else {
		id = 'None';
	}
	territoryId
		.classed('clicked', !!selectedTerritory && (!hoveredTerritory || hoveredTerritory.node() === selectedTerritory.node()))
		.text(id);
}

function checkSelectedTerritory() {
	var isSelectedTerritory = !svgMap.select('path.selected').empty();
	if (!isSelectedTerritory) {
		alert('No territory has been selected');
	}
	return isSelectedTerritory;
}

function saveTerritoryPosition() {
	var selectedTerritory = svgMap.select('path.selected');
	return function(d) {
		d.territory = {
			coordinates: selectedTerritory.getPathCoordinates(),
			xpath: selectedTerritory.xpath()
		};
		return d;
	};
}

// Step 4
function initTerritoryAutocomplete() {
	territoryName = d3.select('#territoryName');
	territoryName.node().focus();

	autocomplete(d3.select('#territoryName').node())
		.dataField("name")
		.width(960)
		.height(500)
		.render();
}

function saveTerritoryName() {
	return function(d) {
		d.territory = {
			id: territoryName.datum().territoryId
		};
		return d;
	};
}

// Step 5
function saveTerritoryPeriod() {
	return function(d) {
		d.territory = {
			period: {
				start: d3.select('#territoryPeriodStart').node().value,
				end: d3.select('#territoryPeriodEnd').node().value
			}
		};
		return d;
	};
}