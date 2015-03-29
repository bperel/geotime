
// Calibrate the background's center, scale and rotation so that the two maps are superimposed
function calibrateMapScale(min, max, inc) {
    min = min || 100;
    max = max || 5000;
    inc = inc || 200;
    var bestScale = {scale: null, ratio: Infinity};

    var newRatio;
    for (var newScale = min; newScale <= max; newScale += inc) {
        applyProjection(getSelectedProjection(), projection.center(), newScale);
        newRatio = getCalibrationPointsDistanceDiffsValue().bgFgDistanceRatio;
        console.log('Calibrating with scale = '+newScale+', ratio = ' + newRatio);
        if (Math.abs(newRatio - 1) < Math.abs(bestScale.ratio -1)) {
            bestScale = {scale: newScale, ratio: newRatio};
        }
    }

    if (inc > 10) {
        calibrateMapScale(bestScale.scale - inc, bestScale.scale + inc, inc / 10);
        console.log('=> Calibrated scale = '+bestScale.scale+', final offset : ' + bestScale.ratio);
    }
    applyProjection(getSelectedProjection(), projection.center(), bestScale.scale, projection.rotate());
}

function calibrateMapRotation(axis, getRatioMethod, min, max, inc) {
    min = min || -89;
    max = max ||  89;
    inc = inc ||   5;
    var rotations = [];

	var initialRotation = projection.rotate();
    var valueNoRotation = getRatioMethod.apply().value;
	var bestRotations = {negative: {rotation: null, value: Infinity}, positive: {rotation: 0, value: valueNoRotation}};
    for (var axisNewRotation = min; axisNewRotation <= max; axisNewRotation += inc) {
        var rotationSign = axisNewRotation < 0 ? 'negative' : 'positive';
        var bestRotationValue = bestRotations[rotationSign].value;

        var rotation = initialRotation;
        rotation[axis] = axisNewRotation;
        applyProjection(getSelectedProjection(), projection.center(), projection.scale(), rotation);
        var currentValue = getRatioMethod.apply().value;

        console.log('Calibrating with rotation = '+axisNewRotation+' on axis ' + axis+', value = ' + currentValue);
        if (bestRotationValue > currentValue) {
            bestRotations[rotationSign] = {rotation: axisNewRotation, value: currentValue};
        }
        rotations.push(currentValue);
    }

	console.log('=> Best rotations : '+JSON.stringify(bestRotations));

    var projectionToApply = initialRotation;
    if (axis === 2) {
        projectionToApply[axis] = bestRotations.positive.rotation;
    }
    else {
        projectionToApply[axis] = bestRotations.negative.rotation;
    }
	applyProjection(getSelectedProjection(), projection.center(), projection.scale(), projectionToApply);

    if (inc > 1) {
        calibrateMapRotation(axis, getRatioMethod, projectionToApply[axis] - inc, projectionToApply[axis] + inc, inc / 10);
    }

}

function getCalibrationPointsDistanceDiffsValue() { // distance diff-based value. Smaller is better
	var pxDistanceSums = {bgMap: 0, fgMap: 0, ratios: []};
	calibrationPoints.forEach(function(point1, i) {
		var bgMapPoint1 = projection([point1.bgMap.lng, point1.bgMap.lat]);
		var fgMapPoint1 = [point1.fgMap.x, point1.fgMap.y];
		calibrationPoints.forEach(function(point2, j) {
			if (i < j) {
				var bgMapPoint2 = projection([point2.bgMap.lng, point2.bgMap.lat]);
				var fgMapPoint2 = [point2.fgMap.x, point2.fgMap.y];
				var bgMapDistance = Math.sqrt(Math.pow(bgMapPoint1[0] - bgMapPoint2[0], 2) + Math.pow(bgMapPoint1[1] - bgMapPoint2[1], 2));
				var fgMapDistance = Math.sqrt(Math.pow(fgMapPoint1[0] - fgMapPoint2[0], 2) + Math.pow(fgMapPoint1[1] - fgMapPoint2[1], 2));
				pxDistanceSums.bgMap += bgMapDistance;
				pxDistanceSums.fgMap += fgMapDistance;
				pxDistanceSums.ratios.push(fgMapDistance / bgMapDistance);
			}
		});
	});

	return {
        value: d3.deviation(pxDistanceSums.ratios),
        bgFgDistanceRatio: d3.mean(pxDistanceSums.ratios),
        diff: pxDistanceSums.bgMap - pxDistanceSums.fgMap
    };
}

function getCalibrationPointsPositionDiffsValue() { // x/y coordinates diff-based value. Smaller is better
    var sum = 0;
    calibrationPoints.forEach(function(point1, i) {
        var bgMapPoint1 = projection([point1.bgMap.lng, point1.bgMap.lat]);
        var fgMapPoint1 = [point1.fgMap.x, point1.fgMap.y];
        calibrationPoints.forEach(function(point2, j) {
            if (i < j) {
                var bgMapPoint2 = projection([point2.bgMap.lng, point2.bgMap.lat]);
                var fgMapPoint2 = [point2.fgMap.x, point2.fgMap.y];
                sum += Math.abs(bgMapPoint1[0] - bgMapPoint2[0]) / Math.abs(fgMapPoint1[0] - fgMapPoint2[0])
                     + Math.abs(bgMapPoint1[1] - bgMapPoint2[1]) / Math.abs(fgMapPoint1[1] - fgMapPoint2[1]);
            }
        });
    });
    return { value: sum};
}

function getClosestPointToCenter() {
	var width  = svgMap.styleIntWithoutPx("width"),
		height = svgMap.styleIntWithoutPx("height"),
		minDistance = null,
		minDistancePointIndex = null;

	var center = {x: width/2, y: height/2};

	calibrationPoints.forEach(function(point, i) {
		var distance = Math.sqrt(Math.pow(point.fgMap.x - center.x, 2) + Math.pow(point.fgMap.y - center.y, 2));
		if (minDistance === null || distance < minDistance) {
			minDistance = distance;
			minDistancePointIndex = i;
		}
	});

	return minDistancePointIndex;
}

var markerRadius = 9;
function addCalibrationDefsMarkers() {
	markersSvg = d3.select("#mapArea")
		.append("svg").attr("id", "markers")
		.attr("width", width)
		.attr("height", mapHeight);

	markersSvg.selectAll('g')
		.data([{type: 'bgMap'}, {type: 'fgMap'}])
		.enter()
		.append('g')
		.attr('class', function(d) { return 'marker-group '+d.type; })
		.attr('transform', function(d) { return 'translate('+(d.type === 'bgMap' ? 0 : -mapPadding)+' 0)'; });

	var defs = markersSvg.append('defs');
	var marker = defs.append('svg:g').attr('id','crosshair-marker');

	marker.selectAll('circle')
		.data([{stroke: '#000000', r: 6}, {stroke: 'inherit', r: 7}])
		.enter().append('circle')
		.attr('style', function(d) { return 'stroke:'+d.stroke; })
		.attr('cx', 9)
		.attr('cy', 9)
		.attr('r', function(d) { return d.r; });

	marker.selectAll('path')
		.data([
			{id: 'up', 	  d: 'M 9,6 L 9,0 z'},
			{id: 'down',  d: 'M 9,12 L 9,18 z'},
			{id: 'left',  d: 'M 6,9 L 0,9 z'},
			{id: 'right', d: 'M 12,9 L 18,9 z'}
		])
		.enter().append('path')
		.attr('style', 'stroke-width:1')
		.attr('id', function(d) { return d.id; })
		.attr('d', function(d) { return d.d; });
}

function addCalibrationMarker(type, index, coordinates) {
	var group = markersSvg.selectAll('g.marker-group').filter(function(d) { return d.type === type; });

	group.selectAll('use').filter(function(d) { return d.pointId === index; })
		.data([{type: type, pointId: index, coordinates: coordinates}])
		.enter().append('use')
		.attr('xlink:href', '#crosshair-marker')
		.each(function(d) { positionCalibrationMarker.call(this, d); });
}

function repositionCalibrationMarkers() {
	var group = markersSvg.selectAll('g.marker-group').filter(function(d) { return d.type === 'bgMap'; });
	group.selectAll('use').each(positionCalibrationMarker);
}

function positionCalibrationMarker(d) {
	if (d.coordinates.lng) {
		var xyCoordinates = projection([d.coordinates.lng, d.coordinates.lat]);
		d.coordinates.x = xyCoordinates[0];
		d.coordinates.y = xyCoordinates[1];
	}
	d.coordinates.x -= markerRadius;
	d.coordinates.y -= markerRadius;

	d3.select(this)
		.attr("x", d.coordinates.x)
		.attr("y", d.coordinates.y);
}