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
							   'Click on the background map to add a point, then click on the foreground map at the corresponding location.' +
                               '<br /><span id="calibrationPointsLength">0</span>&nbsp;<label for="calibrationPointsLength">selected points.</label>'
							  +'<span id="calibrationPoints"></span>'],
			onLoad: [enableCalibrationPointSelection],
			onUnload: [disableCalibrationPointSelection],
			dataUpdate: saveMapProjection,
			buttons: ['done', 'skip', 'cancel']
		}, {
			step: 2, content: ['If necessary, move the superimposed map so that it corresponds to the background borders.'],
			onLoad: [enableMapDragResize],
			dataUpdate: saveMapPosition,
			onUnload: [disableMapDragResize, saveMapLocation],
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
			onUnload: [saveHelperData],
			buttons: ['done', 'cancel']
		}
	];
}

// Step 1
function enableCalibrationPointSelection() {
	svg.on('click', function() {
		if (bgMapDragState === 'inactive') {
			addCalibrationPoint('bgMap', d3.event);
		}
	});

	svgMap.on('click', function() {
		if (!d3.event.defaultPrevented) {
			addCalibrationPoint('fgMap', d3.event);
		}
	})
	.call(svgmap_drag);
}

function disableCalibrationPointSelection() {
	svg.on('click', null);
	svgMap.on('click', null);
}

function addCalibrationPoint(mapType, clickedPoint) {
	var mapOffset = mapType === 'fgMap'
		? svgMap.mapOffset()
		: svg.mapOffset();

	clickedPoint = {
		x: clickedPoint.x - mapOffset.x,
		y: clickedPoint.y - mapOffset.y
	};

	var coordinates = clickedPoint;
	if (mapType === 'bgMap') {
		var latLngCoordinates = projection.invert([clickedPoint.x, clickedPoint.y]);
		coordinates.lng = latLngCoordinates[0];
		coordinates.lat = latLngCoordinates[1];
	}

	addCalibrationMarker(mapType, coordinates);
	showCalibrationPoints();
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
			.html("&nbsp;X Remove point")
			.on('click', function(d, i) {
				calibrationPoints.splice(i, 1);
				showCalibrationPoints()
			});

	calibrationPointsElements.exit().remove();

	d3.select('#calibrationPointsLength').text(calibrationPoints.length);
}

function saveMapProjection() {

	calibrateMapRotation();
    calibrateMapScale();
    calibrateMapCenter();

	return function(d) {
		d.map = {
			id: svgMap.datum().id,
			center: projection.center(),
            rotation: projection.rotate(),
            scale: parseInt(projection.scale()),
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

function saveMapPosition() {
    return function(d) {
        d.map = {
            id: svgMap.datum().id,
            center: projection.center(),
            rotation: projection.rotate(),
            scale: projection.scale(),
            projection: getSelectedProjection()
        };
        return d;
    };
}

function disableMapDragResize() {
	svgMap.on('mousedown.drag', null);
	resizeHandle
		.on('mousedown.drag', null)
		.classed("hidden", true);
}

function saveMapLocation() {
    var mapData = helperSteps.data().filter(function(d) { return d.step === 2; })[0].map;
    validateMapLocation(mapData);
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