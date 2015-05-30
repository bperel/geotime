
// Calibrate the background's center, scale and rotation so that the two maps are superimposed
function calibrateMapScale(min, max, inc) {
    min = min || 100;
    max = max || 5000;
    inc = inc || 200;
    var bestScale = {scale: null, ratio: Infinity};

    var group = markersSvg.selectAll('g.marker-group').filter(function(d) { return d.type === 'bgMap'; });
    var markers = group.selectAll('use');

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

    if (inc > 10) {
        calibrateMapScale(bestScale.scale - inc, bestScale.scale + inc, inc / 10);
        console.log('=> Calibrated scale = '+bestScale.scale+', final offset : ' + bestScale.ratio);
    }
    else {
		applyProjection(getSelectedProjection(), projection.center(), bestScale.scale, projection.rotate());
	}
}

function getCalibrationPointsDistanceDiffsValue() { // distance diff-based value. Smaller is better
	var pxDistanceSums = {bgMap: 0, fgMap: 0, ratios: [], latitudeRatios: []};
	calibrationPoints.forEach(function(point1, i) {
		var bgMapPoint1 = [point1.bgMap.x, point1.bgMap.y];
		var fgMapPoint1 = [point1.fgMap.x, point1.fgMap.y];
		calibrationPoints.forEach(function(point2, j) {
			if (i < j) {
				var bgMapPoint2 = [point2.bgMap.x, point2.bgMap.y];
				var fgMapPoint2 = [point2.fgMap.x, point2.fgMap.y];
				var bgMapDistance = Math.sqrt(Math.pow(bgMapPoint1[0] - bgMapPoint2[0], 2) + Math.pow(bgMapPoint1[1] - bgMapPoint2[1], 2));
				var fgMapDistance = Math.sqrt(Math.pow(fgMapPoint1[0] - fgMapPoint2[0], 2) + Math.pow(fgMapPoint1[1] - fgMapPoint2[1], 2));
				pxDistanceSums.bgMap += bgMapDistance;
				pxDistanceSums.fgMap += fgMapDistance;
				pxDistanceSums.ratios.push(fgMapDistance / bgMapDistance);
				pxDistanceSums.latitudeRatios.push((Math.abs(fgMapPoint1[1] - fgMapPoint2[1])) / Math.abs(bgMapPoint1[1] - bgMapPoint2[1]));
			}
		});
	});

	return {
        value: d3.deviation(pxDistanceSums.ratios),
		latitudeValue: d3.deviation(pxDistanceSums.latitudeRatios),
        bgFgDistanceRatio: d3.mean(pxDistanceSums.ratios),
        diff: pxDistanceSums.bgMap - pxDistanceSums.fgMap
    };
}

function getCalibrationPointsPositionDiffsValue() { // x/y coordinates diff-based value. Smaller is better
    var sum = 0;
    calibrationPoints.forEach(function(point) {
        var bgMapPoint = [point.bgMap.x, point.bgMap.y];
        var fgMapPoint = [point.fgMap.x - svgMap.datum().x, point.fgMap.y - svgMap.datum().y];
		sum += Math.sqrt(Math.pow(bgMapPoint[0] - fgMapPoint[0], 2) + Math.pow(bgMapPoint[1] - fgMapPoint[1], 2));
    });
    return { value: sum};
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

function addCalibrationMarker(type, coordinates) {

	var index = 0;
	while (calibrationPoints[index] && calibrationPoints[index][type]) {
		index++;
	}
	calibrationPoints[index] = calibrationPoints[index] || {};
	calibrationPoints[index][type] = coordinates;

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

function calibrateMapRotation(axisDefaults) {
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


    var group = markersSvg.selectAll('g.marker-group').filter(function(d) { return d.type === 'bgMap'; });
	var markers = group.selectAll('use');

	var min = Infinity;
	var best = null;
	for (var i = axisDefaults[0] - axisCheckRange; i <= axisDefaults[0] + axisCheckRange; i += incdeg) {
		console.log('Test axis 0 : '+i+'deg at '+new Date().toISOString());
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
        applyProjection(getSelectedProjection(), projection.center(), projection.scale(), best);
    }
	else {
        calibrateMapRotation(best)
    }
}