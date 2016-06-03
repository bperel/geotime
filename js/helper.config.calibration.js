var calibrationPoints = [];

function getMarkers() {
    var group = markersSvg.selectAll('g.marker-group').filter(function(d) { return d.type === 'bgPoint'; });
    return group.selectAll('use');
}


// Calibrate the background's center, scale and rotation so that the two maps are superimposed
function calibrateMapScale(min, max, inc) {
    min = min || 100;
    max = max || 5000;
    inc = inc || 200;
    var bestScale = {scale: null, ratio: Infinity};

    var markers = getMarkers();

    var newRatio;
    for (var newScale = min; newScale <= max; newScale += inc) {
        projection.scale(newScale);
        markers.each(positionCalibrationMarker);
        newRatio = getCalibrationPointsDistanceDiffsValue().bgFgDistanceRatio;
        console.log('Calibrating with scale = '+newScale+', ratio = ' + newRatio);
        if (Math.abs(newRatio - 1) < Math.abs(bestScale.ratio -1)) {
            bestScale = {scale: newScale, ratio: newRatio};
        }
    }

	console.log('=> Calibrated scale = '+bestScale.scale+', final offset : ' + bestScale.ratio);

	var new_inc = inc / 10;
    if (new_inc >= 2) {
        calibrateMapScale(bestScale.scale - inc/2, bestScale.scale + inc/2, new_inc);
    }
    else {
		applyProjection(getSelectedProjection(), projection.center(), bestScale.scale, projection.rotate());
	}
}

function calibrateMapCenter() {
    var markers = getMarkers();
    var currentCenter = projection.center();

    var externalMapOffsetToCenter = getExternalMapOffsetToCenter();

    function getDirections(bgMapFirstPoint, fgMapFirstPoint) {
        return {
            x: bgMapFirstPoint.x - (fgMapFirstPoint.x + externalMapOffsetToCenter.x) < 0 ? -1:  1,
            y: bgMapFirstPoint.y - (fgMapFirstPoint.y + externalMapOffsetToCenter.y) < 0 ? 1 : -1
        };
    }

	var groupedCalibrationPoints = getGroupedCalibrationPoints();

    var initialDirections = getDirections(groupedCalibrationPoints[0].bgPoint, groupedCalibrationPoints[0].fgPoint);
    var directions = JSON.parse(JSON.stringify(initialDirections));
    while(initialDirections.x === directions.x || initialDirections.y === directions.y) {
        if (initialDirections.x === directions.x) {
            currentCenter[0]+=directions.x;
        }
        if (initialDirections.y === directions.y) {
            currentCenter[1]+=directions.y;
        }
        projection.center(currentCenter);
        markers.each(positionCalibrationMarker);

        directions = getDirections(groupedCalibrationPoints[0].bgPoint, groupedCalibrationPoints[0].fgPoint);
    }

	currentCenter[0]+=directions.x;
	currentCenter[1]+=directions.y;

    applyProjection(getSelectedProjection(), currentCenter, projection.scale(), projection.rotate());
}

function getCalibrationPointsDistanceDiffsValue() { // distance diff-based value. Smaller is better
	var pxDistanceSums = {bgPoint: 0, fgPoint: 0, ratios: [], latitudeRatios: []};
	var groupedCalibrationPoints = getGroupedCalibrationPoints(true);

	groupedCalibrationPoints.forEach(function(point1, i) {
		var bgMapPoint1 = [point1.bgPoint.x, point1.bgPoint.y];
		var fgMapPoint1 = [point1.fgPoint.x, point1.fgPoint.y];
		groupedCalibrationPoints.forEach(function(point2, j) {
			if (i < j) {
				var bgMapPoint2 = [point2.bgPoint.x, point2.bgPoint.y];
				var fgMapPoint2 = [point2.fgPoint.x, point2.fgPoint.y];
				var bgMapDistance = Math.sqrt(Math.pow(bgMapPoint1[0] - bgMapPoint2[0], 2) + Math.pow(bgMapPoint1[1] - bgMapPoint2[1], 2));
				var fgMapDistance = Math.sqrt(Math.pow(fgMapPoint1[0] - fgMapPoint2[0], 2) + Math.pow(fgMapPoint1[1] - fgMapPoint2[1], 2));
				pxDistanceSums.bgPoint += bgMapDistance;
				pxDistanceSums.fgPoint += fgMapDistance;
				pxDistanceSums.ratios.push(fgMapDistance / bgMapDistance);
				pxDistanceSums.latitudeRatios.push((Math.abs(fgMapPoint1[1] - fgMapPoint2[1])) / Math.abs(bgMapPoint1[1] - bgMapPoint2[1]));
			}
		});
	});

	return {
        value: d3.deviation(pxDistanceSums.ratios),
		latitudeValue: d3.deviation(pxDistanceSums.latitudeRatios),
        bgFgDistanceRatio: d3.mean(pxDistanceSums.ratios),
        diff: pxDistanceSums.bgPoint - pxDistanceSums.fgPoint
    };
}

var markerSide = 9;

function addCalibrationMarker(type, coordinates, showMarkers) {

	var pointId = 0;
	var scope = angular.element('#calibrationPoints').scope();
	var calibrationPoints = scope.calibrationPoints;

	calibrationPoints.forEach(function(calibrationPoint) {
		if (calibrationPoint.type === type && calibrationPoint.pointId >= pointId) {
			pointId = calibrationPoint.pointId + 1;
		}
	});

	calibrationPoints.push({pointId: pointId, type: type, coordinates: coordinates});

	if (showMarkers) {
		markersSvg.repositionCalibrationMarkers(calibrationPoints, type);
	}

	scope.calibrationPoints = calibrationPoints;
}

d3.selection.prototype.repositionCalibrationMarkers = function(calibrationPoints, type) {
	var filter = function() { return !type || d3.select(this).classed(type); };

	var groups = markersSvg.selectAll('g.marker-group').filter(filter);

	groups.each(function() {
		var group = d3.select(this);
		var groupType = d3.select(this).attr('class').match(/(bg|fg)Point/)[0];
		var groupCalibrationPoints = group.selectAll('use')
			.data(calibrationPoints.filter(function(d) { return d.type === groupType; }));

		groupCalibrationPoints
			.exit().remove();

		groupCalibrationPoints
			.enter().append('use')
			.filter(filter)
			.attr('xlink:href', '#crosshair-marker');

		groupCalibrationPoints
			.each(positionCalibrationMarker);
	});

	return this;
};

function getGroupedCalibrationPoints(withProjectedCoords) {
	var scope = angular.element('#calibrationPoints').scope();
	var calibrationPoints = scope.calibrationPoints;
	
	var shownCalibrationPoints = {};
	calibrationPoints.forEach(function(d) {
		if (!(shownCalibrationPoints[d.pointId])) {
			shownCalibrationPoints[d.pointId] = {
				pointId: d.pointId
			};
		}
		shownCalibrationPoints[d.pointId][d.type] = d.coordinates;
		if (d.type === 'bgPoint' && !withProjectedCoords) {
			delete shownCalibrationPoints[d.pointId][d.type].x;
			delete shownCalibrationPoints[d.pointId][d.type].y;
		}
	});

	return d3.values(shownCalibrationPoints);
}

function positionCalibrationMarker(d) {
	var isProjected = d.coordinates.lng !== undefined;
	if (isProjected) {
		var xyCoordinates = projection([d.coordinates.lng, d.coordinates.lat]);
		d.coordinates.x = xyCoordinates[0].round10pow(6);
		d.coordinates.y = xyCoordinates[1].round10pow(6);
	}

	if (d.type === 'bgPoint') {
		d.coordinates.x -= markerSide;
		d.coordinates.y -= markerSide;
	}

	d3.select(this)
		.attr("x", d.coordinates.x)
		.attr("y", d.coordinates.y)
		.attr("class", isProjected && d.coordinates.x > widthSideBySide ? 'out-of-bounds' : '');
}

function calibrateMapRotationForProjection(projectionName, axisDefaults) {
	var incdeg,
        axisCheckRange,
        isPrecise = !!axisDefaults;

    if (axisDefaults) {
        incdeg = .5;
        axisCheckRange = 5;
    }
    else {
        incdeg = 5;
        axisCheckRange = 89;
        axisDefaults = [0,0,0];
    }

    var markers = getMarkers();

	var min = Infinity;
	var best = null;
	for (var i = axisDefaults[0] - axisCheckRange; i <= axisDefaults[0] + axisCheckRange; i += incdeg) {
		//console.log('Test axis 0 : '+i+'deg at '+new Date().toISOString());
		for (var j = axisDefaults[1] - axisCheckRange; j <= axisDefaults[1] + axisCheckRange; j += incdeg) {
			projection.rotate([i,j,0]);
			markers.each(positionCalibrationMarker);
			var value = getCalibrationPointsDistanceDiffsValue().value;
			if (value < min) {
				min = value;
				best = [i,j,0];
			}
		}
	}

	console.log('Best : '+best+' with '+min);

	min = Infinity;
	for (var k = axisDefaults[2] - axisCheckRange; k <= axisDefaults[2] + axisCheckRange; k += incdeg) {
		projection.rotate([best[0],best[1],k]);
		markers.each(positionCalibrationMarker);
		var latitudeValue = getCalibrationPointsDistanceDiffsValue().latitudeValue;
		if (latitudeValue < min) {
			min = latitudeValue;
			best[2] = k;
		}
	}

	console.log(new Date().toISOString());
	console.log('Best : '+best+' with '+min);

    if (isPrecise) {
		return { projection: projectionName, min: min, rotation: best };
    }
	else {
        return calibrateMapRotationForProjection(projectionName, best)
    }
}

function calibrateMapRotation() {

	var currentProjection = projection;

	var bestProjectionResult = {min: Infinity};
	projections.forEach(function(projectionName) {

		projection = d3.geo[projectionName]()
			.center(currentProjection.center())
			.scale(currentProjection.scale())
			.rotate(currentProjection.rotate());

		applyCurrentProjection();

		var calibrationResults =  calibrateMapRotationForProjection(projectionName);
		if (calibrationResults.min < bestProjectionResult.min) {
			bestProjectionResult = JSON.parse(JSON.stringify(calibrationResults));
		}
		console.log('Result for projection ' + projectionName + ': '+JSON.stringify(calibrationResults));
	});

	if (bestProjectionResult.projection) {
		applyProjection(bestProjectionResult.projection, projection.center(), projection.scale(), bestProjectionResult.rotation);
	}

	console.log('Best result : '+JSON.stringify(bestProjectionResult));
}