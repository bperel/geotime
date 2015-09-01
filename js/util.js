var gatewayUrl = 'gateway.php';

function ajaxPost(options, callback) {
    d3.json(gatewayUrl)
        .header("Content-Type", "application/x-www-form-urlencoded")
        .post($.param(options), function(error, data) {
            if (error) {
                alert(error);
            }
            else if (data.error) {
                alert(data.error);
            }
            else {
                if (callback) {
                    callback(error, data);
                }
            }
        });
}

/**
 *
 * @returns Array
 */
d3.selection.prototype.getPathCoordinates = function() {
    var path = this.node();
    var len = path.getTotalLength();
	var ratio = parseInt(svgMap.attr('width')) / svgMap.datum().width;
	var offset = getExternalMapOffsetToCenter();

    var coordinates = [];
    for(var i=0; i<len; i++){
        var p=path.getPointAtLength(i);
        coordinates.push(projection.invert([offset.x + p.x/ratio, offset.y + p.y/ratio]));
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

Number.prototype.round10pow = function(p) {
    p = p || 0;
    return Math.round(this * Math.pow(10, p)) / Math.pow(10, p);
};

d3.selection.prototype.loadTemplate = function(title, processName) {
    var element = this;
    var html =
        '<h5 class="if-active">'+title+'</h5>' +
        '<div class="if-active">[content]</div>';
    if (templates[processName]) {
        element.html(html.replace('[content]', templates[processName]));
    }
    d3.text('templates/'+processName+'.html', function(error, templateHtml) {
        if (error) {
            console.log('Could not load template : '+processName+'.html');
        }
        else {
            element.html(
                html.replace('[content]', templates[processName] = templateHtml)
            );
        }
    });
};