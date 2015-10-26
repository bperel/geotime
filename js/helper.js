var helper = d3.select('nothing');
var helperProcessTabs = d3.selectAll('nothing');
var helperButtonsData = [];
var helperStepsForProcess = [];
var helperStepsData = [];
var helperProcessesData = [];
var templates = []
var territoryId;
var territoryName;

function initHelper(mapFileName, activeProcess) {
	d3.select('#mapTitle').text(mapFileName);

	helper = d3.select("#mapHelper")
		.classed("hidden", false);

	helperProcessTabs = helper.select('#processTabs').selectAll('li')
		.data(helperProcessesData);

	helperProcessTabs
		.enter().append('li')
			.append('a')
				.attr('href', '#')
				.html(function(d) { return d.text; })
				.on('click', function(d) {
					loadProcess(d.name);
				});
	helperProcessTabs.exit().remove();

	loadProcess(activeProcess);
}

function loadProcess(processName) {
	helper.datum({ activeProcess: processName, activeStep: 0 });

	helperProcessesData.forEach(function(processDatum) {
		processDatum.active = processDatum.name === processName;
	});

	helperProcessTabs
		.data(helperProcessesData)
		.classed('active', function(d) { return d.active; });

	helperStepsForProcess = helper.select('div#helperStepsContainer').selectAll('.helperStep')
		.data(helperStepsData.filter(function(d) { return d.process === processName; }));

	helperStepsForProcess
		.enter()
		.append('div')
			.classed('helperStep list-group-item', true);

	helperStepsForProcess.each(function(d) {
		var callback = d.process === processName && d.order === 1 ? activateHelperNextStep : noop;

		d3.select(this).loadTemplate({
			process: d.process,
			name: d.step,
			title: d.title,
			callback: callback
		});
	});

	helperStepsForProcess.exit().remove();
}

function unloadCurrentStep() {
	((helperStepsData.filter(isActiveStepFilter) || [{}])[0]
		.onUnload || []
	)
	.forEach(function (onUnloadAction) {
		onUnloadAction();
	});
}

function activateHelperNextStep() {

	var newStep = ++helper.datum().activeStep;

	if (newStep <= helperStepsForProcess.data().length) {
		helperStepsForProcess
			.classed("active", isActiveStepFilter)
			.filter(isActiveStepFilter)
			.each(function(d) {
				(d.onLoad || []).forEach(function(onLoadAction) {
					onLoadAction(d3.select('#externalSvg').datum());
				});
			})
			.selectAll('button').data(helperButtonsData).enter().append('button')
				.each(function () {
					d3.select(this).on('click', function (btnData) {
						var stepElement = helperStepsForProcess
							.filter(isActiveStepFilter)
							.filter(function (d) {
								return btnData.name !== 'done' || isValidStepFilter(d);
							});
						if (!stepElement.empty()) {
							if (btnData.name === 'done') {
								if (stepElement.datum().dataUpdate) {
									stepElement.datum(stepElement.datum().dataUpdate());
								}
								unloadCurrentStep();
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
					var stepNumber = helperStepsForProcess.data().length;
					return d.name === 'skip' && newStep === stepNumber;
				});
	}
}

function isActiveStepFilter(d) {
	return helper.datum()
		&& helper.datum().activeStep === d.order
		&& helper.datum().activeProcess === d.process;
}

function isValidStepFilter(d) {
	return !d.validate || d.validate();
}

function getHelperStepData(order) {
	order = order || helper.datum().activeStep;
    return helperSteps.data().filter(function(d) { return d.order === order; })[0];
}

function empty() { return {}; }