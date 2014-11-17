var helper = d3.select("#mapHelper");

var dragHelper = d3.behavior.drag()
	.origin(function(d) { return d; })
	.on("dragstart", function() {
		d3.event.sourceEvent.stopPropagation();
	})
	.on("drag", function(d) {
		d.x += d3.event.dx;
		d.y += d3.event.dy;
		d3.select(this)
			.style("margin-left", d.x+"px")
			.style("margin-top",  d.y+"px");
	});

function initHelper() {
	helper
		.datum(function(d) {
			d.activeStep = 0;
			return d;
		})
		.classed("hidden", false);
}

function activateHelperNextStep() {
	var newStep = ++helper.datum().activeStep;
	helper.selectAll('.helperStep').classed("active", function(step) {
		return step === newStep;
	});

	if (svgMap) {
		switch(newStep) {
			case 1:
				svgMap.call(svgmap_drag);
				resizeHandle.call(svgmap_resize);
				break;
			case 4:
				var coordinates = pathToCoordinates(svgMap.select('path.selected').node());
				validateTerritory(svgMap.datum().id, territoryName.node().value, coordinates, svgMap.select('path.selected').xpath());
				break;
			default:
				svgMap.on('mousedown.drag', null);
				resizeHandle.on('mousedown.drag', null);

				if (newStep === 3) {
					territoryName.node().focus();
				}
		}

		resizeHandle.classed("hidden", newStep !== 1);
		territoryName.classed("hidden", newStep !== 3);

		svgMap
			.selectAll("path")
				.on("mouseover", newStep === 2 ? onTerritoryMouseover : null)
				.on("mouseout",  newStep === 2 ? onTerritoryMouseout  : null)
				.on("click",     newStep === 2 ? onTerritoryClick     : null);
	}
}

function ignoreCurrentMap() {
	slider.datum(function(d) {
		d.ignoredMaps.push(svgMap.datum().id);
		return d;
	});
	initExternalSvgMap();
}

function validateTerritory(mapId, territoryName, coordinates, xpath) {
	ajaxPost(
		{ addTerritory: 1, mapId: mapId, territoryName: territoryName, xpath: xpath, coordinates: coordinates },
		function(error, data) {

		}
	);
}

helper
	.datum({x: 0, y: 0})
	.call(dragHelper);

var helperSteps = helper.selectAll('.helperStep')
	.data([1, 2, 3]);

helperSteps
	.select('.helperStepDone')
		.on("click", activateHelperNextStep);

helperSteps
	.select('.helperStepCancel')
		.on("click", ignoreCurrentMap);