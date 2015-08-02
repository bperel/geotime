requirejs.config({
	"paths": {
		"app": "../"
	},
	"shim": {
		"d3.xpath": ["js/vendor/d3/d3.min"],
		"helper": ["js/vendor/d3/d3.min"],
		"helper.config.calibration": ["js/helper"],
		"util": ["js/vendor/d3/d3.min"],
		"map": ["js/vendor/d3/d3.min"]
	}
});

requirejs(dependencies);