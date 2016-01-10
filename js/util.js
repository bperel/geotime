var gatewayUrl = 'gateway.php';
var noop = function() {};
var templates = [];
var pathMaxSavedPoints = 1000;

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

    var coordinates = [];
    for(var i=0; i<len; i+=Math.max(1, len/pathMaxSavedPoints)) {
        var absoluteCenter = getAbsolutePosition(path, path.getPointAtLength(i));
        coordinates.push(projection.invert([absoluteCenter.x, absoluteCenter.y]).round10pow(6));
    }
    return coordinates;
};

function getAbsolutePosition(path, point) {
    var matrix = path.getScreenCTM();
    return {
        x: (matrix.a * point.x) + (matrix.c * point.y) + matrix.e,
        y: (matrix.b * point.x) + (matrix.d * point.y) + matrix.f
    };
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
    if (!property) {
        return null;
    }
    return intWithoutPx(this.style(property));
};

d3.selection.prototype.attrIntWithoutPx = function(attr) {
    if (!attr) {
        return null;
    }
    return intWithoutPx(this.style(attr));
};

function intWithoutPx(value) {
    return value && parseInt(value.replace(/px$/,''));
}

Number.prototype.round10pow = function(p) {
    p = p || 0;
    return Math.round(this * Math.pow(10, p)) / Math.pow(10, p);
};

Array.prototype.round10pow = function(p) {
    if (this[0] && this[1]) {
        return [this[0].round10pow(p), this[1].round10pow(p)];
    }
};

d3.selection.prototype.loadTemplate = function (args) {
    var title             = args.title,
        callback          = args.callback,
        noConditionalShow = args.noConditionalShow,
        templatePath = 'templates/' + (args.process || '') + '/' + args.name +'.html';

    var element = this;
    var html =
        (title ? ('<h5>'+title+'</h5>') : '') +
        (noConditionalShow ? '[content]' : '<div class="if-active">[content]</div>');

    if (templates[templatePath]) {
        element.html(html.replace('[content]', templates[templatePath]));

        if (callback) {
            callback(element);
        }
    }
    else {
        d3.text(templatePath, function(error, templateHtml) {
            if (error) {
                console.log('Could not load template : '+templatePath);
            }
            else {
                element.html(
                    html.replace('[content]', templates[templatePath] = templateHtml)
                );
                if (callback) {
                    callback(element);
                }
            }
        });
    }

    return this;
};