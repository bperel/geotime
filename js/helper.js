var helper;
var helperButtons;
var helperSteps;
var resizeHandle;
var territoryName;

function initHelper() {

	resizeHandle = d3.select('#resizeHandle');
	resizeHandle
		.classed("hidden", true)
		.attr("width", resizeHandleSize)
		.attr("height", resizeHandleSize)
		.select("rect")
			.attr("width", resizeHandleSize)
			.attr("height", resizeHandleSize);

	territoryName = d3.select('#territoryName');

	helper = d3.select("#mapHelper")
		.datum({ activeStep: 0});

	helperSteps = helper.select('ul').selectAll('.helperStep');
	helperSteps.data([
			{ step: 1, content: 'Move the superimposed map so that it corresponds to the background borders.' },
			{ step: 2, content: 'Select with your mouse a country whose name is written on the map or known by you.' },
			{ step: 3, content: '<label for="territoryName">Type in its name :</label><input type="text" id="territoryName" />' }
		]).enter().append('li')
			.classed('helperStep', true)
			.html(function(d) { return '<div>'+d.content+'</div>'; });

	helperButtons = [
		{
			name: 'done', cssClass: 'helperStepDone', text: 'Done !',
			validate: {2: checkSelectedTerritory},
			dataUpdate: {1: saveMapPosition, 2: saveTerritoryPosition, 3: saveTerritoryName},
			click: activateHelperNextStep
		},
		{
			name: 'skip', cssClass: 'helperStepSkip', text: 'Skip this step',
			dataUpdate: {1: empty, 2: empty},
			click: activateHelperNextStep
		},
		{
			name: 'cancel', cssClass: 'helperStepCancel', text: 'Switch to another map',
			dataUpdate: {1: empty, 2: empty, 3: empty},
			click: ignoreCurrentMap
		}
	];

	initTerritoryAutocomplete();
}

function activateHelperNextStep() {
	var newStep = ++helper.datum().activeStep;

	helper.classed("hidden", false);
	helperSteps
		.classed("active", isActiveStepFilter)
		.filter(isActiveStepFilter)
		.selectAll('button').data(helperButtons).enter().append('button')
			.each(function() {
				d3.select(this).on('click', function(d) {
					if (!d.validate[newStep] || d.validate[newStep]()) {
						var stepElement = helperSteps
							.filter(isActiveStepFilter)
							.datum(d.dataUpdate[newStep]());
						d.click(stepElement);
					}
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

function isActiveStepFilter(d) {
	return d.step === helper.datum().activeStep;
}

function empty() { return {}; }