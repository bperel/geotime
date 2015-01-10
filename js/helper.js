var helper;
var helperButtonsData = [];
var helperSteps;
var helperStepsData = [];
var resizeHandle;
var territoryId;
var territoryName;

function initHelper(mapFileName, helperStepsData) {

	resizeHandle = d3.select('#resizeHandle');
	resizeHandle
		.classed("hidden", true)
		.attr("width", resizeHandleSize)
		.attr("height", resizeHandleSize)
		.select("rect")
			.attr("width", resizeHandleSize)
			.attr("height", resizeHandleSize);

	d3.select('#mapTitle').text(mapFileName);

	helper = d3.select("#mapHelper")
		.datum({ activeStep: 0});

	helperSteps = helper.select('ul').selectAll('.helperStep');
	helperSteps.data(helperStepsData).enter().append('li')
		.classed('helperStep', true)
		.html(function(d) { return '<div>'+d.content[0]+'</div><div class="if-active">'+ d.content.slice(1)+'</div>'; });

	// Refresh with the created elements
	helperSteps = helper.select('ul').selectAll('.helperStep');
}

function activateHelperNextStep() {
	if (helper.datum().activeStep > 0) {
		helperSteps
			.filter(isActiveStepFilter)
			.call(function () {
				(this.datum().onUnload || []).forEach(function (onUnloadAction) {
					onUnloadAction();
				});
			});
	}

	var newStep = ++helper.datum().activeStep;

	if (newStep <= helperSteps.data().length) {
		helper.classed("hidden", false);
		helperSteps
			.classed("active", isActiveStepFilter)
			.filter(isActiveStepFilter)
			.call(function() {
				(this.datum().onLoad || []).forEach(function(onLoadAction) {
					onLoadAction();
				});
				this.datum().dataInit && this.datum(this.datum().dataInit());
			})
			.selectAll('button').data(helperButtonsData).enter().append('button')
				.each(function () {
					d3.select(this).on('click', function (btnData) {
						var stepElement = helperSteps
							.filter(isActiveStepFilter)
							.filter(function (d) {
								return btnData.name !== 'done' || isValidStepFilter(d);
							});
						if (!stepElement.empty()) {
							if (btnData.name === 'done' && stepElement.datum().dataUpdate) {
								stepElement.datum(stepElement.datum().dataUpdate());
							}
							btnData.click(stepElement);
						}
					});
				})
				.attr('class', function (d) {
					return d.cssClass;
				})
				.text(function (d) {
					return d.text;
				})
				.classed('hidden', function (d) {
					var stepNumber = helperSteps.data().length;
					return d.name === 'skip' && newStep === stepNumber;
				});
	}
	else {
		validateTerritory(flattenArrayOfObjects(helperSteps.data()));
	}
}

function ignoreCurrentMap() {
	timeSlider.datum(function(d) {
		d.ignoredMaps.push(svgMap.datum().id);
		return d;
	});
	initExternalSvgMap();
}

// Step 1
function enableMapDragResize() {
	svgMap.call(svgmap_drag);
	resizeHandle
		.call(svgmap_resize)
		.classed("hidden", false);
}

function disableMapDragResize() {
	svgMap.on('mousedown.drag', null);
	resizeHandle
		.on('mousedown.drag', null)
		.classed("hidden", true);
}

function initMapData() {
	return function(d) {
		d.map = {
			id: svgMap.datum().id
		};
		return d;
	};
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
		d.map.position = pos;
		d.map.projection = "mercator";
		return d;
	};
}

// Step 2
function enableTerritorySelection() {
	territoryId = d3.select('#territoryId');

	svgMap
		.selectAll("path")
			.on("mouseover", onTerritoryMouseover)
			.on("mouseout",  onTerritoryMouseout)
			.on("click",     onHoveredTerritoryClick);
}

function disableTerritorySelection() {
	svgMap
		.selectAll("path")
			.on("mouseover", null)
			.on("mouseout",  null)
			.on("click",     null);
}

function updateTerritoryId() {
	var id;
	if (hoveredTerritory) {
		id = hoveredTerritory.attr('id');
	}
	else if (selectedTerritory) {
		id = selectedTerritory.attr('id');
	}
	else {
		id = 'None';
	}
	territoryId
		.classed('clicked', !!selectedTerritory && (!hoveredTerritory || hoveredTerritory.node() === selectedTerritory.node()))
		.text(id);
}

function checkSelectedTerritory() {
	var isSelectedTerritory = !svgMap.select('path.selected').empty();
	if (!isSelectedTerritory) {
		alert('No territory has been selected');
	}
	return isSelectedTerritory;
}

function saveTerritoryPosition() {
	var selectedTerritory = svgMap.select('path.selected');
	return function(d) {
		d.territory = {
			coordinates: selectedTerritory.getPathCoordinates(),
			xpath: selectedTerritory.xpath()
		};
		return d;
	};
}

// Step 3
function initTerritoryAutocomplete() {
	territoryName = d3.select('#territoryName');
	territoryName.node().focus();

	autocomplete(d3.select('#territoryName').node())
		.dataField("name")
		.width(960)
		.height(500)
		.render();
}

function saveTerritoryName() {
	return function(d) {
		d.territory = {
			id: territoryName.datum().territoryId
		};
		return d;
	};
}

// Step 4
function saveTerritoryPeriod() {
	return function(d) {
		d.territory = {
			period: {
				start: d3.select('#territoryPeriodStart').node().value,
				end: d3.select('#territoryPeriodEnd').node().value
			}
		};
		return d;
	};
}

function isActiveStepFilter(d) {
	return d.step === helper.datum().activeStep;
}

function isValidStepFilter(d) {
	return !d.validate || d.validate();
}

function empty() { return {}; }