var margin = {top: 10, right: 50, bottom: 10, left: 50},
	sliderWidth = 100 - margin.left - margin.right,
	mapHeight = 500 - margin.bottom - margin.top;

var y = d3.scale.linear()
	.domain([2012, 0])
	.range([0, mapHeight])
	.clamp(true);

var brush = d3.svg.brush()
	.y(y)
	.extent([0, 0])
	.on("brush", brushed);

var sliderSvg = d3.select("body").append("svg")
	.attr("width", sliderWidth + margin.left + margin.right)
	.attr("height", mapHeight + margin.top + margin.bottom)
	.attr("id", "sliderContainer")
	.append("g")
		.attr("transform", "translate(" + margin.left + "," + margin.top + ")");

sliderSvg.append("g")
	.attr("class", "y axis")
	.attr("transform", "translate("+ sliderWidth / 2 + ", 0)")
	.call(d3.svg.axis()
		.scale(y)
		.orient("left")
		.tickFormat(function(d) { return d; })
		.tickSize(0)
		.tickPadding(12))
	.select(".domain")
	.select(function() { return this.parentNode.appendChild(this.cloneNode(true)); })
	.attr("class", "halo");

var slider = sliderSvg.append("g")
	.attr("class", "slider")
	.call(brush);

slider.selectAll(".extent,.resize")
	.remove();

slider.select(".background")
	.attr("sliderWidth", sliderWidth);

var handle = slider.append("circle")
	.attr("class", "handle")
	.attr("transform", "translate("+ sliderWidth / 2 + ", 0)")
	.attr("r", 9);

slider
	.call(brush.extent([2012, 2012]))
	.call(brush.event);

function brushed() {
	var value = brush.extent()[0];

	if (d3.event.sourceEvent) { // not a programmatic event
		value = y.invert(d3.mouse(this)[0]);
		brush.extent([value, value]);
	}

	handle.attr("cy", y(value));
}