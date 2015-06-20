requirejs.config({
	"paths": {
		"js": "js"
	}
});

requirejs(dependencies.concat(guiDependencies), function() {
	initMapPlaceHolders(function() {
		initMapArea();
		loadUI();
		loadHelperConfig();
		getAndShowBgMap("backgroundMap", "data/external/ne_110m_coastline.json", function() {
			applyCurrentProjection();
		});
	});
	d3.select('#loadRandomTerritoryMap').on('click', loadRandomTerritoryMap);
});