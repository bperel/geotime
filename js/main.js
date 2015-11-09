requirejs.config({
	"paths": {
		"jquery": "vendor/jquery/dist/jquery.min",
		"bootstrap.min": "vendor/bootstrap/dist/js/bootstrap.min",
		"d3": "vendor/d3/d3.min"
	},
	"shim": {
		"d3.xpath": ["d3"],
		"helper": ["d3"],
		"helper.config": ["helper"],
		"helper.config.calibration": ["helper.config"],
		"util": ["d3"],
		"map": ["d3"],
		"bootstrap.min": ["jquery"]
	}
});

requirejs(dependencies.concat(guiDependencies), onLoad);