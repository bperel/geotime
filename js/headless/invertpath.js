var page = require('webpage').create(),
    system = require('system'),
    fs = require('fs'),
    svgPath, output, size;


if (system.args.length < 2) {
    console.error('Invalid arguments');
    phantom.exit(1);
} else {
    svgPath = fs.workingDirectory + '/cache/svg/' + system.args[1];
    if (!fs.isFile(svgPath)) {
        console.log(svgPath+' does not exist');
        phantom.exit(1);
    }

    page.onLoadFinished = function() {
		setTimeout(function() {
			var pathCoordinates = page.evaluate(function (args, svgContent) {
				var d3 = window.d3;

				getSelectedProjection = function () {
					return projectionName;
				};

				initMapArea();

				var svgFileName = args[1];
				var pathId = args[2];
				var projectionName = args[3];
				var projectionCenter = args[4].split(',').map(function (value) {
					return parseInt(value);
				});
				var projectionScale = parseInt(args[5]);
				var projectionRotation = args[6].split(',').map(function (value) {
					return parseInt(value);
				});

				projection = d3.geo[projectionName]()
					.center(projectionCenter)
					.scale(projectionScale)
					.rotate(projectionRotation)
					.precision(.01);

				return loadTerritoryMap(svgFileName, {
					id: 'externalSvg',
					projection: projectionName,
					center: projectionCenter,
					scale: projectionScale,
					rotation: projectionRotation
				}, svgContent, function () {
					return d3.select('svg path#' + pathId).getPathCoordinates();
				});
			}, system.args, fs.read(svgPath));
			console.log(pathCoordinates);
		}, 500);
    };

    page.open('http://localhost/geotime/index_headless.html');
}