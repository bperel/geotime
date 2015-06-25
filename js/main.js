requirejs.config({
	"paths": {
		"js": "js"
	},
	"shim": {
		"d3.xpath": ["vendor/d3/d3.min"]
	}
});

requirejs(dependencies.concat(guiDependencies), onLoad);