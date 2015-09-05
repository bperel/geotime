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

	helperProcessesData = [
		{
			name: 'mapLocation',
			text: 'Map location'
		}, {
			name: 'territoryIdentification',
			text: 'Territory identification'
		}
	];

	helperStepsData = [
		{
			process: 'mapLocation',
			step: 1, title: 'Select locations on both maps',
			onLoad: [enableCalibrationPointSelection],
			validate: checkCalibrationPoints,
			onUnload: [disableCalibrationPointSelection],
			dataUpdate: saveMapProjection,
			buttons: ['done', 'skip', 'cancel']
		}, {
			process: 'mapLocation',
			step: 2, title: 'Adjust the map calibration',
			onLoad: [enableMapDragResize],
			dataUpdate: saveMapPosition,
			onUnload: [disableMapDragResize, persistMapLocation],
			buttons: ['done', 'skip', 'cancel']
		}, {
			process: 'territoryIdentification',
			step: 1, title: 'Locate territories',
			onLoad: [enableTerritorySelection,initTerritoryAutocomplete,showLocatedTerritories],
            validate: checkSelectedTerritory,
            dataUpdate: saveTerritoriesPosition,
			onUnload: [disableTerritorySelection, persistTerritoriesPosition],
			buttons: ['done', 'skip', 'cancel']
		}
	];
}

// Step 1
function enableCalibrationPointSelection() {
	svg.on('click', function() {
		if (bgMapDragState !== 'drag') {
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

	var coordinates = {};
	if (mapType === 'bgMap') {
		var latLngCoordinates = projection.invert([clickedPoint.x, clickedPoint.y]);
		coordinates.lng = latLngCoordinates[0].round10pow(6);
		coordinates.lat = latLngCoordinates[1].round10pow(6);
	}
	else {
		coordinates = clickedPoint;
	}

	addCalibrationMarker(mapType, coordinates);
	showCalibrationPoints();
}

function removeCalibrationPoint(d, pointId) {
	var toDelete = [];
	calibrationPoints.forEach(function(calibrationPoint, i) {
		if (calibrationPoint.pointId === pointId) {
			toDelete.push(i);
		}
	});
	toDelete.reverse().forEach(function(calibrationPointIndex) {
		calibrationPoints.splice(calibrationPointIndex, 1);
	});

	showCalibrationPoints();
	markersSvg.repositionCalibrationMarkers();
}

function showCalibrationPoints() {
	var groupedCalibrationPoints = getGroupedCalibrationPoints();

	var calibrationPointsElements = d3.select('#calibrationPoints').selectAll('.calibrationPoint').data(groupedCalibrationPoints);
	calibrationPointsElements.enter().append('li').classed({calibrationPoint: true, 'list-group-item': true});

	calibrationPointsElements
		.append("span")
			.classed('removeCalibrationPoint', true)
			.on('click', removeCalibrationPoint)
			.append('button')
				.attr('type', 'button')
				.classed('btn btn-default btn-xs', true)
				.append('span')
					.classed('glyphicon glyphicon-remove', true);

	var calibrationPointTypes = [{
		text: 'Background point',
		property: 'bgMap',
		buttonClass: 'btn-info'
	}, {
		text: 'Foreground point',
		property: 'fgMap',
		buttonClass: 'btn-primary'
	}];

	calibrationPointsElements.each(function(calibrationPointData) {
		d3.select(this).append('div')
			.selectAll('span.badge').data(calibrationPointTypes)
			.enter().append('span')
				.attr('class', function(d) { return d.property; })
				.classed('badge', true)
				.html(function(d) { return d.text; })
				.append('span')
					.classed('glyphicon glyphicon-ok', true)
					.attr('title', function(d) { return JSON.stringify(calibrationPointData[d.property]);});
	});

	calibrationPointsElements.exit().remove();

	d3.select('#calibrationPointsLength').text(groupedCalibrationPoints.length);
}

function checkCalibrationPoints() {
	if (calibrationPoints.length < 4) {
		alert('Please select at least 4 points');
		return false;
	}
	return true;
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
            projection: getSelectedProjection(),
            calibrationPoints: calibrationPoints
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

function persistMapLocation() {
    validateMapLocation(getHelperStepData(2).map);
}

// Step 3
function enableTerritorySelection() {
	territoryId = d3.select('#territoryId');

	svgMap
		.selectAll("path")
		.on("mouseover", onTerritoryMouseover)
		.on("mouseout",  onTerritoryMouseout)
		.on("click",     onHoveredTerritoryClick);

    d3.select('#addTerritory').on('click', function() {
        locatedTerritories.push({
            element: selectedTerritory,
            period: {
                start: d3.select('#territoryPeriodStart').node().value,
                end: d3.select('#territoryPeriodEnd').node().value
            },
            referencedTerritory: {
                id: territoryName.datum().territoryId,
                name: territoryName.datum().territoryName
            }
        });
        showLocatedTerritories();
    });
}

function initTerritoryAutocomplete() {
    territoryName = d3.select('#territoryName');
    territoryName.node().focus();

    autocomplete(d3.select('#territoryName').node())
        .dataField("name")
        .width(960)
        .height(500)
        .render();
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

function showLocatedTerritories() {
    var locatedTerritoriesElements = d3.select('#locatedTerritories').selectAll('.locatedTerritory').data(locatedTerritories);
    locatedTerritoriesElements.enter().append('div').classed('locatedTerritory', true);

    locatedTerritoriesElements
        .text(function (d) {
            return d.referencedTerritory.name;
        })
        .append("span")
            .classed('removeLocatedTerritory', true)
            .html("&nbsp;X Remove")
            .on('click', function(d, i) {
            locatedTerritories.splice(i, 1);
                showLocatedTerritories()
            });

    locatedTerritoriesElements.exit().remove();

    d3.select('#locatedTerritoriesNumber').text(locatedTerritories.length);
}

function checkSelectedTerritory() {
	var isSelectedTerritory = locatedTerritories.length;
	if (!isSelectedTerritory) {
		alert('No territory has been selected');
	}
	return isSelectedTerritory;
}

function saveTerritoriesPosition() {
	return function(d) {
		d.territories = locatedTerritories.map(function(locatedTerritory) {
			if (!locatedTerritory.id) {
				locatedTerritory.xpath = locatedTerritory.element.xpath();
			}
            delete locatedTerritory.element;

            return locatedTerritory;
        });
		return d;
	};
}

function persistTerritoriesPosition() {
    validateTerritories(svgMap.datum().id, getHelperStepData().territories);
}