var margin = {top: 10, right: 50, bottom: 10, left: 50},
	sliderWidth = 200 - margin.left - margin.right,
	mapHeight = sliderHeight = 500 - margin.bottom - margin.top,

	minYear = 0;
	maxYear = 2012;

var y = d3.scale.linear()
	.domain([maxYear, minYear])
	.range([0, sliderHeight])
	.clamp(true);

var brush = d3.svg.brush()
	.y(y)
	.extent([0, 0])
	.on("brush", brushed);

var sliderSvg = d3.select("body").append("svg")
	.attr("width",  sliderWidth  + margin.left + margin.right)
	.attr("height", sliderHeight + margin.top  + margin.bottom)
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
	.attr("width", sliderWidth)
	.attr("height", sliderHeight);

var handle = slider.append("circle")
	.attr("class", "handle")
	.attr("transform", "translate("+ sliderWidth / 2 + ", 0)")
	.attr("r", 9);

slider
	.call(brush.extent([maxYear, maxYear]))
	.call(brush.event);

var periodRegex = /([0-9]{4})\-([0-9]{4})/;
var optimalCoverage;
var colorInterpolator = d3.scale.linear()
	.domain([0,1])
	.interpolate(d3.interpolateRgb)
	.range(["#ff0000", "#008000"]);

d3.json("gateway.php?getCoverage", function(error, coverageInfo) {

	optimalCoverage = coverageInfo.optimalCoverage;

	slider.selectAll("rect.period")
		.data(coverageInfo.periodsAndCoverage)
		.enter()
			.append("rect")
			.classed("period", true)
			.attr("x", 46)
			.attr("y", function(d) {
				var endDate = d.period.match(periodRegex)[2];
				return sliderHeight*(1 - (endDate / (maxYear - minYear)));
			})
			.attr("width", 8)
			.attr("height", function(d) {
				var dates = d.period.match(periodRegex);
				return sliderHeight*(dates[2] - dates[1] + 1) / (maxYear - minYear);
			})
			.attr("fill", function(d) {
				return colorInterpolator(d.coverage / optimalCoverage);
			});
});

var svgMap;
var isLoadingSvg;

initSvgMap();

function initSvgMap() {
	svgMap = d3.select("foo");
	svgMap.remove();
	isLoadingSvg = false;
}

function brushed() {
	var value = y.invert(d3.mouse(this)[1]);
	if (!isNaN(value)) {
		brush.extent([value, value]);
		handle.attr("cy", y(value));

		var year = parseInt(value);
		if (!isLoadingSvg) {
			d3.json("gateway.php?getSvg&year="+year, function(error, incompleteMapInfo) {
				if (incompleteMapInfo) {
					var mapFileName = incompleteMapInfo.fileName;
					if (svgMap.empty() || svgMap.filter(function(d) {
						return d.fileName === mapFileName;
					}).empty()) {
						isLoadingSvg = true;
						initSvgMap();

						d3.xml("cache/svg/"+mapFileName, "image/svg+xml", function(xml) {
							svgMap = d3.select(svg.node().appendChild(document.importNode(xml.documentElement, true)))
								.attr("name", mapFileName)
								.classed("externalSvg", true)
								.datum(incompleteMapInfo);
							isLoadingSvg = false;
						});
					}
				}
				else {
					initSvgMap();
				}
			});
		}
	}
}