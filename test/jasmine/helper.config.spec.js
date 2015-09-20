var testHelperSteps;
var testButtonData;

var testValue;

describe('Calibration tests', function() {
    var territoryName = 'simple';
    var territoryFilename = 'simple.svg';

    beforeEach(function(){

        gatewayUrl = '../../gateway.php';
        projections = ['mercator'];

        jasmine.getJSONFixtures().fixturesPath = 'base';
        loadJSONFixtures("test/phpunit/_data/countries.json");

        jasmine.getFixtures().fixturesPath = 'base';
        loadFixtures("map-placeholders.html");

        jasmine.Ajax.install();

        initBackgroundMap();
        resizeBackgroundMap(widthSuperimposed, mapHeight);
        addCalibrationDefsMarkers();
        initProjectionSelect();
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
        calibrationPoints = [];
    });

    d3.selection.prototype.clickOnMap = function(clickedPoint) {
        var offset = this === svg ? {x: 200, y: 0} : svgMap.mapOffset();

        d3.event = {x: clickedPoint.x + offset.x, y: clickedPoint.y + offset.y};
        this.on('click')();

        return this;
    };

    describe('Adding and removing calibration points', function() {
        it('should add a calibration point when clicking on a map', function() {
            var markerRadius = 9;

            loadTerritoryMap(true);

            var calibrationPointsTexts = d3.select('#calibrationPoints').selectAll('.calibrationPoint');

            expect(calibrationPointsTexts.size()).toBe(0);
            expect(calibrationPoints.length).toBe(0);
            expect(markersSvg.selectAll('g.marker-group use').size()).toBe(0);

            enableCalibrationPointSelection();

            // Bg point
            var clickedPoint = {x: width/2, y: mapHeight/2 +10};
            svg.clickOnMap(clickedPoint);

            expect(calibrationPoints.length).toBe(1);
            expect(calibrationPoints[0].type).toEqual('bgMap');
            expect(calibrationPoints[0].coordinates.x).toBe(clickedPoint.x - markerRadius);
            expect(calibrationPoints[0].coordinates.y).toBe(clickedPoint.y - markerRadius);
            // Longitude and latitude are equal to 0 because we clicked on the middle of the map
            expect(calibrationPoints[0].coordinates.lng).toBe(0);
            expect(calibrationPoints[0].coordinates.lat).toBe(0);

            calibrationPointsTexts = d3.select('#calibrationPoints').selectAll('.calibrationPoint');
            expect(calibrationPointsTexts.size()).toBe(1);
            expect(markersSvg.selectAll('g.marker-group use').size()).toBe(1);

            expect(calibrationPointsTexts.text()).toStartWith(
                'bg : '+JSON.stringify({x: calibrationPoints[0].coordinates.x, y: calibrationPoints[0].coordinates.y, lng: 0, lat: 0})
            );

            // Fg point
            clickedPoint = {x: 55, y: 30};
            svgMap.clickOnMap(clickedPoint);

            expect(calibrationPoints.length).toBe(2);
            expect(calibrationPoints[1].type).toEqual('fgMap');
            expect(calibrationPoints[1].coordinates.x).toBe(clickedPoint.x - markerRadius);
            expect(calibrationPoints[1].coordinates.y).toBe(clickedPoint.y - markerRadius);

            expect(d3.select('#calibrationPoints').selectAll('.calibrationPoint').size()).toBe(1);
            expect(markersSvg.selectAll('g.marker-group use').size()).toBe(2);

        });

        it('should remove a calibration point when clicking on the "Remove point" link', function() {
            loadTerritoryMap(true);
            enableCalibrationPointSelection();

            var clickedPoint = {x: width/2, y: mapHeight/2 +10};
            svg.clickOnMap(clickedPoint);

            var firstPointRemoveLink = d3.select('#calibrationPoints').select('.calibrationPoint .removeCalibrationPoint');
            firstPointRemoveLink.on('click')(firstPointRemoveLink.datum(), 0);

            expect(calibrationPoints.length).toBe(0);
            expect(markersSvg.selectAll('g.marker-group use').size()).toBe(0);
        });
    });
});