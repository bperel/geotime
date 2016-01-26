requirejs.config({
	"paths": {
		"jquery": "vendor/jquery/dist/jquery.min",
		"bootstrap": "vendor/bootstrap/dist/js/bootstrap.min",
		"d3": "vendor/d3/d3.min",
		"angular": "vendor/angular/angular.min",
		"ngRoute": "vendor/angular-route/angular-route.min"
	},
	"shim": {
		"d3.xpath": ["d3"],
		"helper": ["d3"],
		"helper.config": ["helper"],
		"helper.config.calibration": ["helper.config"],
		"util": ["d3"],
		"map": ["d3"],
		"bootstrap": {
			"deps" :['jquery']
		},
		"angular": {
			"exports": "angular"
		},
		"ngRoute": {
			deps: ["angular"]
		}
	},
	"dep": ["app"]
});

requirejs(dependencies.concat(guiDependencies), function(app, angular) {
	app.init(angular);
});