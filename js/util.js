var gatewayUrl = 'gateway.php';

function ajaxPost(options, callback) {
    d3.json(gatewayUrl)
        .header("Content-Type", "application/x-www-form-urlencoded")
        .post($.param(options), callback);
}

/**
 *
 * @returns Array
 */
d3.selection.prototype.getPathCoordinates = function() {
    var path = this.node();
    var len = path.getTotalLength();
    var coordinates = [];
    for(var i=0; i<len; i++){
        var p=path.getPointAtLength(i);
        coordinates.push(projection.invert([p.x, p.y]));
    }
    return coordinates;
};

function flattenArrayOfObjects(array) {
    var obj = {};
    array.forEach(function(arrayVal) {
        d3.entries(arrayVal).forEach(function(keyAndVal) {
            obj[keyAndVal.key] = $.extend({}, obj[keyAndVal.key] || {}, keyAndVal.value);
        });
    });
    return obj;
}

var markerRadius = 9;
function addDefsMarkers() {
	markersSvg = d3.select("#mapArea")
		.append("svg").attr("id", "markers")
		.attr("width", width)
		.attr("height", mapHeight);

	markersSvg.selectAll('g')
		.data([{type: 'bgMap'}, {type: 'fgMap'}])
		.enter()
			.append('g')
			.attr('class', function(d) { return 'marker-group '+d.type; });

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

function addMarker(type, index, coordinates) {
	var group = markersSvg.selectAll('g.marker-group').filter(function(d) { return d.type === type; });

	group.selectAll('use').filter(function(d) { return d.pointId === index; })
		.data([{type: type, pointId: index, coordinates: coordinates}])
		.enter().append('use')
			.attr('xlink:href', '#crosshair-marker')
			.attr('x', function(d) { return d.coordinates.x - markerRadius})
			.attr('y', function(d) { return d.coordinates.y - markerRadius});
}

d3.selection.prototype.styleIntWithoutPx = function(property) {
    if (!property) {
        return null;
    }
    var value = this.style(property);
    return value && parseInt(value.replace(/px$/,''));
};