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

	helperSteps
		.classed("active", isActiveStepFilter)
		.filter(isActiveStepFilter)
		.selectAll('button').data(helperButtons).enter().append('button')
			.each(function() {
				d3.select(this).on('click', function(d) {
					var stepElement = helperSteps
						.filter(isActiveStepFilter)
						.datum(d.dataUpdate[newStep]());
					d.click(stepElement);
				});
			})
			.attr('class', function(d) { return d.cssClass; })
			.text(function(d) { return d.text; })
			.classed('hidden', function(d) { return d3.keys(d.dataUpdate).indexOf(newStep+'') === -1; });

	if (svgMap) {
		switch(newStep) {
			case 1:
				svgMap.call(svgmap_drag);
				resizeHandle.call(svgmap_resize);
				break;
			case 4:
				validateTerritory(flattenArrayOfObjects(helperSteps.data()));
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
				.on("mouseover", newStep === 2 ? onTerritoryMouseover    : null)
				.on("mouseout",  newStep === 2 ? onTerritoryMouseout     : null)
				.on("click",     newStep === 2 ? onHoveredTerritoryClick : null);
	}
}

function ignoreCurrentMap() {
	timeSlider.datum(function(d) {
		d.ignoredMaps.push(svgMap.datum().id);
		return d;
	});
	initExternalSvgMap();
}

function saveMapPosition() {
	var left   = svgMap.styleIntWithoutPx("margin-left"),
		top    = svgMap.styleIntWithoutPx("margin-top"),
		width  = svgMap.styleIntWithoutPx("width"),
		height = svgMap.styleIntWithoutPx("height");
	var pos = [
		projection.invert([left,        top]),
		projection.invert([left+width,  top+height])
	];

	return function(d) {
		d.map = {
			id: svgMap.datum().id,
			position: pos,
			projection: "mercator"
		};
		return d;
	};
}

function saveTerritoryPosition() {
	var selectedTerritory = svgMap.select('path.selected');
	return function(d) {
		d.territory = {
			coordinates: pathToCoordinates(selectedTerritory.node()),
			xpath: selectedTerritory.xpath()
		};
		return d;
	};
}

function saveTerritoryName() {
	return function(d) {
		d.territory = {
			name: territoryName.node().value
		};
		return d;
	};
}

function validateTerritory(data) {
	ajaxPost(
		{
			addTerritory: 1,
			mapId: data.map.id,
			mapProjection: data.map.projection,
			mapPosition: data.map.position,
			territoryName: data.territory.name,
			xpath: data.territory.xpath,
			coordinates: data.territory.coordinates
		},
		function(error, data) {

		}
	);
}

function isActiveStepFilter(d) {
	return d.step === helper.datum().activeStep;
}

function empty() { return {}; }

var helperButtons = [
	{name: 'done', cssClass: 'helperStepDone', text: 'Done !', dataUpdate: {1: saveMapPosition, 2: saveTerritoryPosition, 3: saveTerritoryName}, click: activateHelperNextStep},
	{name: 'skip', cssClass: 'helperStepSkip', text: 'Skip this step', dataUpdate: {1: empty, 2: empty}, click: activateHelperNextStep},
	{name: 'cancel', cssClass: 'helperStepCancel', text: 'Switch to another map', dataUpdate: {1: empty, 2: empty, 3: empty}, click: ignoreCurrentMap}
];

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

var helper = d3.select("#mapHelper")
	.datum({x: 0, y: 0})
	.call(dragHelper);

var helperSteps = helper.selectAll('.helperStep')
	.data(d3.range(1,4).map(function(d) { return {step: d}; }));
