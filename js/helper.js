var helper = d3.select('nothing');
var helperButtonsData = [];
var helperSteps;
var helperStepsData = [];
var helperProcessesData = [];
var resizeHandle;
var territoryId;
var territoryName;

function initHelper(mapFileName, helperStepsData, activeProcess) {

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
		.datum({ activeProcess: activeProcess, activeStep: 0});

	helperProcessesData.forEach(function(processDatum) {
		processDatum.active = processDatum.name === activeProcess;
	});

	helper.select('#processTabs').selectAll('li').remove();

	helper.select('#processTabs').selectAll('li')
		.data(helperProcessesData).enter().append('li')
			.classed('active', function(d) { return d.active; })
				.append('a')
					.attr('href', '#')
					.html(function(d) { return d.text});

	helperSteps = helper.select('ul#helperStepsContainer').selectAll('.helperStep');
	helperSteps.data(helperStepsData).exit();
	helperSteps.data(helperStepsData).enter().append('li')
		.classed('helperStep', true)
		.html(function(d) { return '<div>'+d.content[0]+'</div><div class="if-active">'+ d.content.slice(1)+'</div>'; });

	// Refresh with the created elements
	helperSteps = helper.select('ul#helperStepsContainer').selectAll('.helperStep');
}

function activateHelperNextStep(skipUnloadAction) {
	if (helper.datum().activeStep > 0 && !skipUnloadAction) {
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
					return ['btn', 'btn-default', d.cssClass].join(' ');
				})
				.text(function (d) {
					return d.text;
				})
				.classed('hidden', function (d) {
					var stepNumber = helperSteps.data().length;
					return d.name === 'skip' && newStep === stepNumber;
				});
	}
}

function isActiveStepFilter(d) {
	return d.step === helper.datum().activeStep;
}

function isValidStepFilter(d) {
	return !d.validate || d.validate();
}

function getHelperStepData(step) {
    step = step || helper.datum().activeStep;
    return helperSteps.data().filter(function(d) { return d.step === step; })[0];
}

function empty() { return {}; }