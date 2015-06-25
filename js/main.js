requirejs.config({
	urlArgs: "v=" + (new Date()).getTime(),
	"paths": {
		"js": "js"
	},
	"shim": {
		"d3.xpath": ["vendor/d3/d3.min"],
		"helper": ["vendor/d3/d3.min"],
		"helper.config.calibration": ["helper"],
		"util": ["vendor/d3/d3.min"],
		"map": ["vendor/d3/d3.min"]
	}
});

requirejs(dependencies.concat(guiDependencies), onLoad);