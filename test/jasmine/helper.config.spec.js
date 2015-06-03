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
    });

    afterEach(function() {
        jasmine.Ajax.uninstall();
        svgMap = null;
    });

    describe('Adding calibration points', function() {
        it('should add a calibration point when clicking on a map', function() {
            var markerRadius = 9;
            var bgMapOffset = {x: 200, y: 0};

            loadTerritoryMap();

            expect(calibrationPoints.length).toEqual(0);
            expect(markersSvg.selectAll('g.marker-group use').size()).toEqual(0);

            enableCalibrationPointSelection();

            var clickedPoint = {x: 480, y: 250};
            d3.event = {x: clickedPoint.x + bgMapOffset.x, y: clickedPoint.y + bgMapOffset.y};
            svg.on('click')();

            expect(calibrationPoints.length).toEqual(1);
            expect(calibrationPoints[0].bgMap).toExist();
            expect(Math.abs(calibrationPoints[0].bgMap.x - (clickedPoint.x - markerRadius))).toBeLessThan(0.001);
            expect(Math.abs(calibrationPoints[0].bgMap.y - (clickedPoint.y - markerRadius))).toBeLessThan(0.001);

        });
    });
});