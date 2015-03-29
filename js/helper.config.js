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
			click: empty
		}
	];

	helperStepsData = [
		{
			step: 1, content: ['Select at least 4 points on the maps.',
							   '<span id="calibrationPointsLength">0</span>&nbsp;<label for="calibrationPointsLength">selected points.</label>'
							  +'<span id="calibrationPoints"></span>'],
			onLoad: [enableCalibrationPointSelection],
			onUnload: [disableCalibrationPointSelection],
			dataUpdate: saveMapProjection,
			buttons: ['done', 'skip', 'cancel']
		}, {
			step: 2, content: ['Move the superimposed map so that it corresponds to the background borders.'],
			onLoad: [enableMapDragResize],
			dataInit: initMapData,
			dataUpdate: saveMapPosition,
			onUnload: [disableMapDragResize],
			buttons: ['done', 'skip', 'cancel']
		}, {
			step: 3, content: ['Select with your mouse a country whose name is written on the map or known by you.',
							   'Chosen territory : <span id="territoryId">None</span>'],
			onLoad: [enableTerritorySelection],
			dataUpdate: saveTerritoryPosition, validate: checkSelectedTerritory,
			onUnload: [disableTerritorySelection],
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
			onUnload: saveHelperData,
			buttons: ['done', 'cancel']
		}
	];
}

// Step 1
function enableCalibrationPointSelection() {
	svg.on('click', function() {
		if (bgMapDragState === 'inactive') {
			var mapOffsetLeft = svg.styleIntWithoutPx("margin-left") + mapPadding;
			var mapOffsetTop = svg.styleIntWithoutPx("margin-top");
			var coordinates = {
				x: d3.event.x - mapOffsetLeft,
				y: d3.event.y - mapOffsetTop
			};
			var latLngCoordinates = projection.invert([coordinates.x, coordinates.y]);
			var pointIndex = addCalibrationPoint('bgMap', {lng: latLngCoordinates[0], lat: latLngCoordinates[1]});
			addMarker('bgMap', pointIndex, coordinates);
		}
	});

	svgMap
		.on('click', function() {
			if (!d3.event.defaultPrevented) {
				var mapOffsetLeft = svgMap.styleIntWithoutPx("margin-left") + mapPadding;
				var mapOffsetTop = svgMap.styleIntWithoutPx("margin-top");
				var coordinates = {
					x: d3.event.x - mapOffsetLeft,
					y: d3.event.y - mapOffsetTop
				};
				var pointIndex = addCalibrationPoint('fgMap', coordinates);
				addMarker('fgMap', pointIndex, {x: d3.event.x - mapPadding, y: d3.event.y});
			}
		})
		.call(svgmap_drag);
}

function disableCalibrationPointSelection() {
	svg.on('click', null);
	svgMap.on('click', null);
}

function addCalibrationPoint(mapType, point) {
	var index = 0;
	while (calibrationPoints[index] && calibrationPoints[index][mapType]) {
		index++;
	}
	calibrationPoints[index] = calibrationPoints[index] || {};
	calibrationPoints[index][mapType] = point;
	showCalibrationPoints();
	return index;
}

function showCalibrationPoints() {
	var calibrationPointsElements = d3.select('#calibrationPoints').selectAll('.calibrationPoint').data(calibrationPoints);
	calibrationPointsElements.enter().append('div').classed('calibrationPoint', true);

	calibrationPointsElements
		.text(function (d) {
			var textParts = [];
			if (d.bgMap) { textParts.push('bg : '+JSON.stringify(d.bgMap)); }
			if (d.fgMap) { textParts.push('fg : '+JSON.stringify(d.fgMap)); }
			return textParts.join(' - ');
		})
		.append("span")
			.classed('removeCalibrationPoint', true)
			.html("&nbsp;X")
			.on('click', function(d, i) {
			calibrationPoints.splice(i, 1);
				showCalibrationPoints()
			});

	calibrationPointsElements.exit().remove();

	d3.select('#calibrationPointsLength').text(calibrationPoints.length);
}

function saveMapProjection() {

	var closestPointIndex = getClosestPointToCenter();
	var centerCoords = [calibrationPoints[closestPointIndex].bgMap.lng, calibrationPoints[closestPointIndex].bgMap.lat];
	var scale = 140 * Math.abs(calibrationPoints[0].fgMap.x - calibrationPoints[1].fgMap.x) / Math.abs(calibrationPoints[0].bgMap.lng - calibrationPoints[1].bgMap.lng) ;

	applyProjection(getSelectedProjection(), centerCoords, scale);
	centerExternalMap();


    calibrateMapRotation(1, getCalibrationPointsDistanceDiffsValue);
    calibrateMapScale();
    //calibrateMapRotation(2);
    //calibrateMapScale();

	return function(d) {
		d.map = {
			id: svgMap.datum().id,
			center: centerCoords,
			projection: getSelectedProjection()
		};
		return d;
	};
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
	var center = projection.invert([
		left+width/2,
		top+height/2
	]);
	var scale = parseInt(projection.scale());

	return function(d) {
		d.map = {
			center: center,
			scale:  scale
		};
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

function saveHelperData() {
	validateTerritory(flattenArrayOfObjects(helperSteps.data()));
}