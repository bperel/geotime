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
			onUnload: [disableMapDragResize, persistMapLocation],
			buttons: ['done', 'skip', 'cancel']
		}, {
			step: 3, content: ['Locate territories.',
							   'Located territories : <span id="locatedTerritoriesNumber"></span>' +
                               '<div id="locatedTerritories"></div>' +
                               '<br /><div id="addTerritorySection" class="section"><div class="title">Add territory location</div>' +
                               'Currently selected territory : <span id="territoryId">None</span>' +
                               '<br /><div id="currentTerritory" class="hidden">' +
                               '<table><tr><td><label for="territoryName">Name :</label></td>' +
                               '<td><input type="text" id="territoryName" /></td></tr></table>' +
                               '<br />had these borders ' +
                               '<label for="territoryPeriodStart">from </label><input type="number" id="territoryPeriodStart" />' +
                               '<label for="territoryPeriodEnd"  > to  </label><input type="number" id="territoryPeriodEnd" />' +
                               '<input type="button" id="addTerritory" class="validate" value="Add territory"/></div>'],
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

	var coordinates = clickedPoint;
	if (mapType === 'bgMap') {
		var latLngCoordinates = projection.invert([clickedPoint.x, clickedPoint.y]);
		coordinates.lng = latLngCoordinates[0].round10pow(6);
		coordinates.lat = latLngCoordinates[1].round10pow(6);
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
				showCalibrationPoints();
                repositionCalibrationMarkers();
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
            projection: getSelectedProjection(),
            calibrationPoints: calibrationPoints.map(removeBgMapProjectedCoordinates)
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
	var isSelectedTerritory = !svgMap.select('path.selected').empty();
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