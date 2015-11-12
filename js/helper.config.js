function loadHelperConfig() {
	helperButtonsData = [
		{
			name: 'done', cssClass: 'helperStepDone', text: 'Done !',
			click: activateHelperNextStep
		},
		{
			name: 'skip', cssClass: 'helperStepSkip', text: 'Skip this step',
			click: activateHelperNextStep
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
			order: 1, step: 'select-calibration-points',
			title: 'Select locations on both maps',
			onLoad: [enableCalibrationPointSelection, loadCalibrationPoints, showMapsSideBySide],
			validate: checkCalibrationPoints,
			onUnload: [disableCalibrationPointSelection, unloadCalibrationPoints],
			dataUpdate: saveMapProjection,
			buttons: ['done', 'skip']
		}, {
			process: 'mapLocation',
			order: 2, step: 'adjust',
			title: 'Adjust the map calibration',
			onLoad: [initProjectionSelect, showMapsSuperimposed],
			dataUpdate: saveMapPosition,
			onUnload: [disableMapDragResize],
			afterValidate: [persistMapLocation],
			buttons: ['done', 'skip']
		}, {
			process: 'territoryIdentification',
			order: 1, step: 'locate-territories',
			title: 'Locate territories',
			onLoad: [loadLocatedTerritories, showLocatedTerritories, hideBackgroundMapIfNotCalibrated, showMapsSuperimposed, initTerritorySelectionAndAutocomplete],
            validate: checkSelectedTerritory,
            dataUpdate: saveTerritoriesPosition,
			afterValidate: [persistTerritoriesPosition],
			onUnload: [disableTerritorySelection, showBackgroundMap],
			buttons: ['done', 'skip']
		}
	];
}

// Process 1, step 1
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
	});
}

function loadCalibrationPoints(mapDatum) {
	calibrationPoints = [];
	if (mapDatum.calibrationPoints) {
		for (var i = 0; i < mapDatum.calibrationPoints.length; i++) {
			var calibrationPoint = mapDatum.calibrationPoints[i];
			addCalibrationMarker("fgMap", calibrationPoint.fgPoint, false);
			addCalibrationMarker("bgMap", calibrationPoint.bgPoint, false);
		}
	}
	markersSvg
		.repositionCalibrationMarkers()
		.classed('hidden', false);
	showCalibrationPoints();
}

function showMapsSideBySide(mapDatum) {
	resizeBackgroundMap(widthSideBySide, mapHeight);
	positionExternalMap(true);

	svgMap.classed('semi-transparent', false);
}

function showMapsSuperimposed(mapDatum) {
	resizeBackgroundMap(widthSuperimposed, mapHeight);
	positionExternalMap(false);

	svgMap.classed('semi-transparent', !!mapDatum.projection);
}

function hideBackgroundMapIfNotCalibrated(mapDatum) {
	svg.classed('hidden', !mapDatum.projection);
}

function showBackgroundMap() {
	svg.classed('hidden', false);
}

function disableCalibrationPointSelection() {
	svg.on('click', null);
	svgMap.on('click', null);
}

function unloadCalibrationPoints() {
	markersSvg.classed('hidden', true);
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
		coordinates.x -= markerSide;
		coordinates.y -= markerSide;
	}

	addCalibrationMarker(mapType, coordinates, true);
	showCalibrationPoints();
}

function removeCalibrationPoint(d) {
	var toDelete = [];
	calibrationPoints.forEach(function(calibrationPoint, i) {
		if (calibrationPoint.pointId === d.pointId) {
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

	var calibrationPointTypes = [{
		text: 'Background point',
		property: 'bgMap',
		buttonClass: 'btn-info'
	}, {
		text: 'Foreground point',
		property: 'fgMap',
		buttonClass: 'btn-primary'
	}];

	var calibrationPointsElements = d3.select('#calibrationPoints').selectAll('.calibrationPointContainer').data(groupedCalibrationPoints);
	calibrationPointsElements.enter()
		.append('div')
			.classed('calibrationPointContainer', true);

	calibrationPointsElements
		.each(function() {
			d3.select(this).loadTemplate({
				name: 'calibrationPoints',
				callback: function(calibrationPointsItem) {
					var calibrationPointData = calibrationPointsItem.datum();

					calibrationPointsItem.select('.removeCalibrationPoint')
						.on('click', removeCalibrationPoint);

					calibrationPointsItem.select('li').selectAll('span.badge').data(
						calibrationPointTypes.filter(function(calibrationPointType) {
							return !!calibrationPointData[calibrationPointType.property];
						})
					)
						.enter().append('span')
							.attr('title', function(d) { return JSON.stringify(calibrationPointData[d.property]); })
							.attr('class', function(d) { return d.property; })
							.classed('badge', true)
							.html(function(d) { return d.text; })
								.append('span')
									.classed('glyphicon glyphicon-ok', true);
				}
			});
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

// Process 1, step 2

function initProjectionSelect(mapDatum) {
	projectionSelection = d3.select('#projectionSelection');

	projectionSelection.selectAll('option')
		.data(projections.map(function(projectionName) {
			return { name: projectionName };
		}))
		.enter().append('option')
		.text(function (d) {
			return d.name;
		})
		.attr('selected', function(d) { return mapDatum && mapDatum.projection === d.name ? 'selected' : null; })
		.on('change', function () {
			applyProjection(getSelectedProjection(), projection.center(), projection.scale(), projection.rotate());
		});
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
}

function persistMapLocation() {
    validateMapLocation(getHelperStepData(2).map);
}

// Process 2, step 1

var locatedTerritoriesElements;
var hoveredTerritory = d3.select('nothing');
var selectedTerritory = d3.select('nothing');

function enableTerritorySelection() {
	selectedTerritory = d3.select('nothing');

	svgMap
		.classed("onTop", true)
		.selectAll("path")
			.on("mouseover", function() { d3.select(this).toggleTerritoryHighlight(true); })
			.on("mouseout",  function() { d3.select(this).toggleTerritoryHighlight(false); })
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
		hideNewTerritoryForm();
    });

	d3.select('#cancelTerritory').on('click', hideNewTerritoryForm);
}

function editTerritory(datum) {
	var form = d3.select('#addTerritorySection');
	form.select('#territoryName').property('value', datum.referencedTerritory.name);
	form.select('#territoryPeriodStart').property('value', datum.startDate.date);
	form.select('#terrritoryName').property('value', datum.endDate.date);

	d3.select('#currentTerritory').classed('hidden', false);
}

function hideNewTerritoryForm() {
	d3.select('#addTerritorySection').remove();
	initTerritorySelectionAndAutocomplete();
}

function initTerritorySelectionAndAutocomplete() {
	clearHoveredAndSelectedTerritories();

	d3.select('#locatedTerritories')
		.append('li')
			.attr('id', 'addTerritorySection')
			.classed('list-group-item', true)
			.loadTemplate({
				name: 'addLocatedTerritory',
				callback: function() {
					territoryId = d3.select('#territoryId');
					territoryName = d3.select('#territoryName');
					territoryName.node().focus();

					autocomplete(d3.select('#territoryName').node())
							.dataField("name")
							.width(960)
							.height(500)
							.render();

					enableTerritorySelection();
				}
			});
}

function disableTerritorySelection() {
	svgMap
		.classed("onTop", false)
		.selectAll("path")
			.on("mouseover", null)
			.on("mouseout",  null)
			.on("click",     null);
	clearHoveredAndSelectedTerritories();
}

function clearHoveredAndSelectedTerritories() {
	hoveredTerritory.classed("hovered", false);
	hoveredTerritory = d3.select('nothing');

	selectedTerritory.classed("selected", false);
	selectedTerritory = d3.select('nothing');
}

function updateTerritoryLabel() {
	var id = (!hoveredTerritory.empty()  && hoveredTerritory.attr('id'))
		  || (!selectedTerritory.empty() && selectedTerritory.attr('id'))
		  || 'None';

	territoryId
		.classed('clicked',
				!selectedTerritory.empty()
			 && (hoveredTerritory.empty() || hoveredTerritory.node() === selectedTerritory.node()))
		.text(id);
}

function loadLocatedTerritories(mapDatum) {
	if (mapDatum.territories) {
		locatedTerritories = mapDatum.territories.filter(function (d) {
			return d.referencedTerritory;
		});
	}
}

function showLocatedTerritories() {
    locatedTerritoriesElements = d3.select('#locatedTerritories').selectAll('.locatedTerritory').data(locatedTerritories);
    locatedTerritoriesElements.enter()
		.append('li')
		.classed('locatedTerritory list-group-item', true)
		.loadTemplate({
			name: 'locatedTerritory',
			callback: function(element) {
				element
					.select('.territoryName')
					.text(function (d) { return d.referencedTerritory.name; });
				element
					.select('.notLocated')
					.classed('hidden', function(d) { return !!d.polygon; });
				element.select('.editLocatedTerritory')
					.on('click', function (d, i) {
						editTerritory(d);
					});
				element.select('.removeLocatedTerritory')
					.on('click', function (d, i) {
						locatedTerritories.splice(i, 1);
						showLocatedTerritories();
					});
			}
		})
		.each(function(d) {
			if (d.xpath) {
				var territoryElement = svgMap.xpathForSvgChild(d.xpath);
				if (territoryElement.empty()) {
					console.warn('Could not locate territory with XPath '+ d.xpath);
				}
				else {
					territoryElement
						.classed('already-identified', true)
						.datum({id: d.id});
				}
			}
		});

    locatedTerritoriesElements.exit().remove();

    d3.select('#locatedTerritoriesNumber').text(locatedTerritories.length);
}

d3.selection.prototype.toggleTerritoryHighlight = function(toggle) {
	hoveredTerritory = this
		.classed("hovered", toggle)
		.toggleTerritoryLabelHighlight(toggle);

	if (!toggle) {
		hoveredTerritory = d3.select('nothing');
	}

	var isAlreadyIdentified = !hoveredTerritory.empty() && hoveredTerritory.datum() && hoveredTerritory.datum().id;
	if (!isAlreadyIdentified) {
		updateTerritoryLabel();
	}

	return this;
};

d3.selection.prototype.toggleTerritoryLabelHighlight = function(toggle) {
	var territoryDbId = this.datum() && this.datum().id;
	locatedTerritoriesElements
		.filter(function(d) { return territoryDbId === d.id; })
			.select('.territoryName')
			.classed('bold', toggle);

	return this;
};

function onHoveredTerritoryClick() {
	var hoveredTerritoryIsSelected = hoveredTerritory.node() === selectedTerritory.node();

	if (!selectedTerritory.empty()) {
		selectedTerritory.classed("selected", false);
	}

	if (hoveredTerritoryIsSelected) {
		selectedTerritory = d3.select('nothing');
	}
	else {
		selectedTerritory = hoveredTerritory;
		selectedTerritory.classed("selected", true);
	}
	updateTerritoryLabel();
	d3.select('#currentTerritory').classed('hidden', false);
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
				locatedTerritory.xpath = locatedTerritory.element.xpathForSvgChild();
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