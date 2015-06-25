requirejs.config({
	urlArgs: "v=" + (new Date()).getTime(),
	"baseUrl": "js",
	"paths": {
		"app": "../"
	},
	"shim": {
		"d3.xpath": ["vendor/d3/d3.min"],
		"util": ["vendor/d3/d3.min"],
		"map": ["vendor/d3/d3.min"]
	}
});

requirejs(dependencies);