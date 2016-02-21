function loadHelperConfig() {
	helperButtonsData = [
		{
			name: 'done', cssClass: 'helperStepDone', text: 'Done !',
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
			onLoad: [enableCalibrationPointSelection, loadCalibrationPoints, showMapsSideBySide, showBackgroundMap, enableMapDragResize],
			validate: checkCalibrationPoints,
			onUnload: [disableMapDragResize, disableCalibrationPointSelection, unloadCalibrationPoints],
			dataUpdate: saveMapProjection
		}, {
			process: 'mapLocation',
			order: 2, step: 'adjust',
			title: 'Adjust the map calibration',
			onLoad: [initProjectionSelect, showMapsSuperimposed, enableMapDragResize],
			dataUpdate: saveMapPosition,
			onUnload: [disableMapDragResize],
			afterValidate: [persistMapLocation]
		}, {
			process: 'territoryIdentification',
			order: 1, step: 'locate-territories',
			title: 'Locate territories',
			onLoad: [hideBackgroundMapIfNotCalibrated, showMapsSuperimposed],
            validate: checkSelectedTerritory,
            dataUpdate: saveTerritoriesPosition,
			afterValidate: [persistTerritoriesPosition],
			onUnload: [disableTerritorySelection]
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

function enableMapDragResize() {
	svg
		.call(bgSvgmap_drag)
		.call(zoom);
	dragAction.classed('hidden', false);
}

function disableMapDragResize() {
	svg.on('mousedown.drag', null);
	dragAction.classed('hidden', true);
}

function persistMapLocation() {
    validateMapLocation(getHelperStepData(helper.datum().activeProcess, 2).map);
}

// Process 2, step 1

function enableTerritorySelection() {
	svgMap
		.classed("onTop", true)
		.selectAll("path")
			.on("mouseover", function() { d3.select(this).toggleTerritoryHighlight(true); })
			.on("mouseout",  function() { d3.select(this).toggleTerritoryHighlight(false); })
			.on("click",     onHoveredTerritoryClick);
}

function disableTerritorySelection() {
	svgMap
		.classed("onTop", false)
		.selectAll("path")
			.on("mouseover", null)
			.on("mouseout",  null)
			.on("click",     null);
}

d3.selection.prototype.animateTerritoryPathOn = function(direction, duration) {
	this
		.datum(function(d) {
			d = d || {};
			d.initialFill = d.initialFill || d3.rgb(d3.select(this).style('fill'));
			return d;
		})
		.filter(function(d) { return d.initialFill.toString() !== '#000000'; })
			.transition().duration(duration).ease('linear')
			.style('fill', function(d) { return d.initialFill[direction === 'in' ? 'brighter' : 'darker'](1.5); })
			.each("end", function() { d3.select(this).animateTerritoryPathOn(direction === 'in' ? 'out' : 'in', duration); });
	return this;
};

d3.selection.prototype.animateTerritoryPathOff = function() {
	this
		.filter(function(d) { return d.initialFill && d.initialFill.toString() !== '#000000'; })
			.transition().duration(0).ease('linear')
			.style('fill', function(d) { return d.initialFill.toString(); });
	return this;
};

d3.selection.prototype.toggleTerritoryHighlight = function(toggle) {
	var scope = angular.element('#locatedTerritories').scope();
	scope.toggleTerritoryHighlight(this, toggle);
	scope.$apply();
	return this;
};

function onHoveredTerritoryClick() {
	var scope = angular.element('#locatedTerritories').scope();
	scope.editTerritory();
	scope.$apply();
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
			delete locatedTerritory.polygon;
            return locatedTerritory;
        });
		return d;
	};
}

function persistTerritoriesPosition() {
    validateTerritories(svgMap.datum().id, getHelperStepData().territories);
}