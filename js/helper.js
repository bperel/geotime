var helper = d3.select('nothing');
var helperButtonsData = [];
var helperSteps;
var helperStepsData = [];
var helperProcessesData = [];
var templates = [];
var resizeHandle;
var territoryId;
var territoryName;

function initHelper(mapFileName, activeProcess) {

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

	var helperProcessTabs = helper.select('#processTabs').selectAll('li')
		.data(helperProcessesData);

	helperProcessTabs.enter().append('li')
		.append('a')
			.attr('href', '#')
			.html(function(d) { return d.text});
	helperProcessTabs.classed('active', function(d) { return d.active; });
	helperProcessTabs.exit().remove();


	helperSteps = helper.select('div#helperStepsContainer').selectAll('.helperStep');
	var helperStepsForProcess = helperSteps.data(helperStepsData);

	helperStepsForProcess.enter()
		.append('div')
			.classed('helperStep list-group-item', true)
			.each(function(d) {
				d3.select(this).loadTemplate(d.title, d.process, d.process === activeProcess);
			});

	// Refresh with the created elements
	helperSteps = helper.select('div#helperStepsContainer').selectAll('.helperStep');

	helperStepsForProcess.classed('hidden', function(d) { return d.process !== activeProcess; });
	helperStepsForProcess.exit().remove();
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
			.each(function(d) {
				(d.onLoad || []).forEach(function(onLoadAction) {
					onLoadAction();
				});
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