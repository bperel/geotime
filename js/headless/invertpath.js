var page = require('webpage').create(),
	system = require('system'),
	localserver = require('./localserver'),
	fs = require('fs'),
	svgPath;

if (system.args.length < 7) {
	console.error('Invalid arguments');
	phantom.exit(1);
} else {
	svgPath = fs.workingDirectory + '/' + system.args[1];
	if (!fs.isFile(svgPath)) {
		console.log(svgPath + ' does not exist');
		phantom.exit(1);
	}

	localserver.create();

	page.open('http://'+localserver.url+'/index_headless.html', function () {
		setTimeout(function () {
			var pathCoordinates = page.evaluate(function (args, svgContent) {
				var d3 = window.d3;

				getSelectedProjection = function () {
					return projectionName;
				};

				initBackgroundMap();
				resizeBackgroundMap(widthSuperimposed, mapHeight);

				var svgFileName = args[1];
				var xpath = args[2];
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

				return loadTerritoryMapData(svgFileName, {
					id: 'externalSvg',
					projection: projectionName,
					center: projectionCenter,
					scale: projectionScale,
					rotation: projectionRotation
				}, svgContent, function () {
					return svgMap.xpathForSvgChild(xpath).getPathCoordinates();
				});
			}, system.args, fs.read(svgPath));

			console.log(JSON.stringify(pathCoordinates));
			localserver.close();
			phantom.exit(0);
		}, 500);
	});
}