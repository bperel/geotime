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

function activateHelperNextStep() {
	var newStep = ++d3.select('#mapHelper').datum().activeStep;
	d3.selectAll('#mapHelper .helperStep').classed("active", function(step) {
		return step === newStep;
	});

	if (svgMap) {
		svgMap
			.selectAll("path")
				.on("mouseover", newStep === 2 ? onTerritoryMouseover : null)
				.on("mouseout",  newStep === 2 ? onTerritoryMouseout  : null);
	}
}

d3.select('#mapHelper')
	.datum({x: 0, y: 0})
	.call(dragHelper);

var helperSteps = d3.selectAll('#mapHelper .helperStep')
	.data([1, 2, 3]);

helperSteps
	.select('.helperStepDone')
		.on("click", activateHelperNextStep);