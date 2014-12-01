
var margin = {top: 10, right: 50, bottom: 10, left: 50},
	sliderWidth = 200 - margin.left - margin.right,
	mapHeight = sliderHeight = 500 - margin.bottom - margin.top;


var timeSliderMinYear = 0,
	timeSliderMaxYear = 2012;

var timeSlider,
	timeSliderBrush,
	timeSliderYScale,
	timeSliderHandle;

function showTimeSlider() {

	initTimeSliderBrush();

	var sliderSvg = d3.select("body").append("svg")
		.attr("width", sliderWidth + margin.left + margin.right)
		.attr("height", sliderHeight + margin.top + margin.bottom)
		.attr("id", "sliderContainer")
		.append("g")
		.attr("transform", "translate(" + margin.left + "," + margin.top + ")");

	sliderSvg.append("g")
		.attr("class", "y axis")
		.attr("transform", "translate(" + sliderWidth / 2 + ", 0)")
		.call(d3.svg.axis()
			.scale(timeSliderYScale)
			.orient("left")
			.tickFormat(function (d) {
				return d;
			})
			.tickSize(0)
			.tickPadding(12))
		.select(".domain")
		.select(function () {
			return this.parentNode.appendChild(this.cloneNode(true));
		})
		.attr("class", "halo");

	timeSlider = sliderSvg.append("g")
		.attr("class", "slider")
		.call(timeSliderBrush);

	timeSlider.selectAll(".extent,.resize")
		.remove();

	timeSlider.select(".background")
		.attr("width", sliderWidth)
		.attr("height", sliderHeight);

	timeSliderHandle = timeSlider.append("circle")
		.attr("class", "handle")
		.attr("transform", "translate(" + sliderWidth / 2 + ", 0)")
		.attr("r", 9);

	timeSlider.call(timeSliderBrush.extent([timeSliderMaxYear, timeSliderMaxYear]));

	var optimalCoverage;
	var colorInterpolator = d3.scale.linear()
		.domain([0, 1])
		.interpolate(d3.interpolateRgb)
		.range(["#ff0000", "#008000"]);

	ajaxPost(
		{getCoverage: true},
		function (error, coverageInfo) {
			optimalCoverage = coverageInfo.optimalCoverage;
			timeSlider
				.datum({ignoredMaps: []})
				.selectAll("rect.period")
				.data(coverageInfo.periodsAndCoverage)
				.enter()
				.append("rect")
					.classed("period", true)
					.attr("x", 46)
					.attr("y", function (d) {
						return sliderHeight * (1 - (d.end / (timeSliderMaxYear - timeSliderMinYear)));
					})
					.attr("width", 8)
					.attr("height", function (d) {
						return sliderHeight * (d.end - d.start + 1) / (timeSliderMaxYear - timeSliderMinYear);
					})
					.attr("fill", function (d) {
						return colorInterpolator(d.coverage / optimalCoverage);
					});
		}
	);
}

function initTimeSliderBrush() {
	timeSliderYScale = d3.scale.linear()
		.domain([timeSliderMaxYear, timeSliderMinYear])
		.range([0, sliderHeight])
		.clamp(true);

	timeSliderBrush = d3.svg.brush()
		.y(timeSliderYScale)
		.extent([0, 0])
		.on("brushend", function() {
			var value = timeSliderYScale.invert(d3.mouse(this)[1]);
			if (!isNaN(value)) {
				timeSliderBrush.extent([value, value]);
				timeSliderHandle.attr("cy", timeSliderYScale(value));

				loadExternalSvgForYear(parseInt(value));
			}

			return false;
		});
}

$(function() {
	showTimeSlider();
});