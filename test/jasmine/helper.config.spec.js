var testHelperSteps;
var testButtonData;

var testValue;

describe('Calibration tests', function() {
    var territoryName = 'simple';
    var territoryFilename = 'simple.svg';

    beforeEach(function(){

        gatewayUrl = '../../gateway.php';

        jasmine.getJSONFixtures().fixturesPath = 'base';
        loadJSONFixtures("test/phpunit/_data/countries.json");

        jasmine.getFixtures().fixturesPath = 'base';
        loadFixtures("map-placeholders.html");

        jasmine.Ajax.install();

        initMapArea();
        showBgMap("backgroundMap", getJSONFixture("test/phpunit/_data/countries.json"));

        spyOn(window, "ajaxPost").and.callFake(function(options, callback) {
            var response;
            var requestType = Object.keys(options)[0];
            switch(requestType) {
                case 'getSvg':
                    response = {
                        id: territoryName,
                        fileName: territoryFilename
                    };
                break;
            }
            callback('', response);
        });

        spyOn(d3, 'xml').and.callFake(function(path, mime, callback) {
            callback(( new window.DOMParser() ).parseFromString(
                '<svg width="110" height="60" id="simple" xmlns="http://www.w3.org/2000/svg">'
                    +'<rect x="10" y="10" height="50" width="100" style="stroke:#ff0000; fill: #0000ff"/>'
                +'</svg>',
                mime)
            );
        });

        d3.select('body').append('div').html(
            '<br />' +
            '<span id="calibrationPointsLength">0</span>&nbsp;<label for="calibrationPointsLength">selected points.</label>' +
            '<span id="calibrationPoints"></span>'
        );

    });

    afterEach(function() {
        jasmine.Ajax.uninstall();
        svgMap = null;
    });

    describe('Adding calibration points', function() {
        it('should add a calibration point when clicking on a map', function() {
            var markerRadius = 9;

            loadTerritoryMap();

            var bgMapOffset = {x: 200, y: 0};
            var fgMapOffset = svgMap.mapOffset();

            expect(d3.select('#calibrationPoints').select('.calibrationPoint').size()).toEqual(0);
            expect(calibrationPoints.length).toEqual(0);
            expect(markersSvg.selectAll('g.marker-group use').size()).toEqual(0);

            enableCalibrationPointSelection();

            // Bg point
            var clickedPoint = {x: 480, y: 250};
            d3.event = {x: clickedPoint.x + bgMapOffset.x, y: clickedPoint.y + bgMapOffset.y};
            svg.on('click')();

            expect(calibrationPoints.length).toEqual(1);
            expect(calibrationPoints[0].bgMap).toExist();
            expect(calibrationPoints[0].fgMap).not.toExist();
            expect(calibrationPoints[0].bgMap.x).toEqual(clickedPoint.x - markerRadius);
            expect(calibrationPoints[0].bgMap.y).toEqual(clickedPoint.y - markerRadius);

            expect(d3.select('#calibrationPoints').select('.calibrationPoint').size()).toEqual(1);
            expect(markersSvg.selectAll('g.marker-group use').size()).toEqual(1);

            // Fg point
            clickedPoint = {x: 55, y: 30};
            d3.event = {x: clickedPoint.x + fgMapOffset.x, y: clickedPoint.y + fgMapOffset.y};
            svgMap.on('click')();

            expect(calibrationPoints.length).toEqual(1);
            expect(calibrationPoints[0].fgMap).toExist();
            expect(calibrationPoints[0].fgMap.x).toEqual(clickedPoint.x - markerRadius);
            expect(calibrationPoints[0].fgMap.y).toEqual(clickedPoint.y - markerRadius);

            expect(d3.select('#calibrationPoints').select('.calibrationPoint').size()).toEqual(1);
            expect(markersSvg.selectAll('g.marker-group use').size()).toEqual(2);

        });
    });
});