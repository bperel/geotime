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

	// Step 2
	territoryId = d3.select('#territoryId');

	// Step 3
	territoryName = d3.select('#territoryName');
	initTerritoryAutocomplete();
}

function activateHelperNextStep() {
	var newStep = ++helper.datum().activeStep;

	if (newStep <= helperSteps.data().length) {
		helper.classed("hidden", false);
		helperSteps
			.classed("active", isActiveStepFilter)
			.filter(isActiveStepFilter)
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

		if (svgMap) {
			switch (newStep) {
				case 1:
					svgMap.call(svgmap_drag);
					resizeHandle.call(svgmap_resize);
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
				.on("mouseout", newStep === 2 ? onTerritoryMouseout : null)
				.on("click", newStep === 2 ? onHoveredTerritoryClick : null);
		}
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

// Step 2
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
function saveTerritoryName() {
	return function(d) {
		d.territory = {
			name: territoryName.node().value
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