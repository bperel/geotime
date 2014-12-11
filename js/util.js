var gatewayUrl = 'gateway.php';

function ajaxPost(options, callback) {
    d3.json(gatewayUrl)
        .header("Content-Type", "application/x-www-form-urlencoded")
        .post($.param(options), callback);
}

/**
 *
 * @param path SVGPathElement
 * @returns Array
 */
function pathToCoordinates(path) {
    var len = path.getTotalLength();
    var coordinates = [];
    for(var i=0; i<len; i++){
        var p=path.getPointAtLength(i);
        var currentCoordinates = projection.invert([p.x, p.y]);
        if (!currentCoordinates) {
            console.error("Projection inversion produced an error");
            return null;
        }
        coordinates.push(currentCoordinates);
    }
    return coordinates;
}

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
    var value = this.style(property);
    return value && parseInt(value.replace(/px$/,''));
};