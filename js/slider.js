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
	.call(brush.extent([maxYear, maxYear]));

var periodRegex = /([0-9]{4})\-([0-9]{4})/;
var optimalCoverage;
var colorInterpolator = d3.scale.linear()
	.domain([0,1])
	.interpolate(d3.interpolateRgb)
	.range(["#ff0000", "#008000"]);

d3.json("gateway.php?getCoverage", function(error, coverageInfo) {

	optimalCoverage = coverageInfo.optimalCoverage;

	slider
		.datum({selectedPeriod: null})
		.selectAll("rect.period")
		.data(coverageInfo.periodsAndCoverage)
		.enter()
			.append("rect")
			.classed("period", true)
			.attr("x", 46)
			.attr("y", function(d) {
				return sliderHeight*(1 - (d.end / (maxYear - minYear)));
			})
			.attr("width", 8)
			.attr("height", function(d) {
				return sliderHeight*(d.end - d.start + 1) / (maxYear - minYear);
			})
			.attr("fill", function(d) {
				return colorInterpolator(d.coverage / optimalCoverage);
			});
});

var svgMap = null;
var isLoading;

initSvgMap();

function initSvgMap() {
	$('#externalSvg').remove();
	if (svgMap) {
		svgMap = null;
	}
	isLoading = false;
	d3.select("#mapHelper").classed("hidden", true);
}

function brushed() {
	var value = y.invert(d3.mouse(this)[1]);
	if (!isNaN(value)) {
		brush.extent([value, value]);
		handle.attr("cy", y(value));

		var year = parseInt(value);
		if (!isLoading) {
			isLoading = true;
			d3.json("gateway.php?getSvg&year="+year, function(error, incompleteMapInfo) {
				if (incompleteMapInfo) {
					var mapFileName = incompleteMapInfo.fileName;
					if (!svgMap || svgMap.datum().fileName !== mapFileName) {
						initSvgMap();

						d3.xml("cache/svg/"+mapFileName, "image/svg+xml", function(xml) {
							svgMap = d3.select(d3.select("#mapArea").node().appendChild(document.importNode(xml.documentElement, true)))
								.attr("name", mapFileName)
								.attr("id", "externalSvg")
								.classed("externalSvg", true)
								.call(svgmap_drag);

							svgMap
								.datum({
									fileName: incompleteMapInfo.fileName,
									x: 0,
									y: 0,
									width:  parseInt(svgMap.attr("width")),
									height: parseInt(svgMap.attr("height"))
								})
								.selectAll("path").each(function() {
									d3.select(this)
										.on("mouseover", onTerritoryMouseover)
										.on("mouseout", onTerritoryMouseout);
								});

							dragmove.call(svgMap.node(), svgMap.datum());

							d3.select("#mapArea")
								.append("svg")
									.attr("id", "resizeHandle")
									.style("left", 200 + svgMap.datum().width - 16)
									.style("top", svgMap.datum().height - 16)
									.attr("width",  16)
									.attr("height", 16)
									.call(svgmap_resize)
									.append("rect")
										.attr("x", 0)
										.attr("y", 0)
										.attr("width", 16)
										.attr("height", 16);

							d3.select("#mapHelper").classed("hidden", false);

							isLoading = false;
						});
					}
				}
				else {
					initSvgMap();
				}
			});
		}
	}

	return false;
}