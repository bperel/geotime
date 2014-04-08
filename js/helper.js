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

d3.select('#mapHelper')
	.datum({x: 0, y: 0})
	.call(dragHelper);