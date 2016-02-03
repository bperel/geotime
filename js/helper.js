var helper = d3.select('nothing');
var helperProcessTabs = d3.selectAll('nothing');
var helperButtonsData = [];
var helperStepsForProcess = [];
var helperStepsData = [];
var helperProcessesData = [];
var territoryId;
var territoryName;

function initHelper(activeProcess) {
}

function loadProcess(processName) {

}

function unloadCurrentStep() {
	((helperStepsData.filter(isActiveStep)[0] || {})
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
			.classed("active", isActiveStep)
			.filter(isActiveStep)
			.each(function(d) {
				(d.onLoad || []).forEach(function(onLoadAction) {
					onLoadAction(d3.select('#externalSvg').datum());
				});
			})
			.selectAll('button').data(helperButtonsData).enter().append('button')
				.each(function () {
					d3.select(this).on('click', function (btnData) {
						var stepElement = helperStepsForProcess
							.filter(isActiveStep)
							.filter(function (d) {
								return btnData.name !== 'done' || isValidStepFilter(d);
							});
						if (!stepElement.empty()) {
							if (btnData.name === 'done') {
								if (stepElement.datum().dataUpdate) {
									stepElement.datum(stepElement.datum().dataUpdate());
								}
								(stepElement.datum().afterValidate || [] )
									.forEach(function (afterValidateAction) {
										afterValidateAction(stepElement.datum());
									});

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

function isActiveStep(d) {
	return helper.datum()
		&& helper.datum().activeStep === d.order
		&& helper.datum().activeProcess === d.process;
}

function isValidStepFilter(d) {
	return !d.validate || d.validate();
}

function getHelperStepData(process, order) {
	process = process || helper.datum().activeProcess;
	order = order || helper.datum().activeStep;
    return helperStepsData.filter(function(d) { return d.order === order && d.process === process; })[0];
}