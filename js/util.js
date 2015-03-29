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

d3.selection.prototype.styleIntWithoutPx = function(property) {
    if (!property) {
        return null;
    }
    var value = this.style(property);
    return value && parseInt(value.replace(/px$/,''));
};