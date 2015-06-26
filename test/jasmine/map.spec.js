describe('Map tests', function() {
    beforeEach(function(){
        gatewayUrl = '../../gateway.php';

        jasmine.getJSONFixtures().fixturesPath = 'base';
        loadJSONFixtures("test/phpunit/_data/countries.json");

        jasmine.getFixtures().fixturesPath = 'base';
        loadFixtures("placeholders/map.html");
        loadFixtures("placeholders/map-projection.html");

        // then install the mock
        jasmine.Ajax.install();
    });

    afterEach(function() {
        jasmine.Ajax.uninstall();
    });

    describe('Map placeholders', function() {
        it('should be loaded', function() {
            expect(d3.select("#mapHelper").empty()).toBeFalsy();
        });
    });

    describe('Background map', function() {
        beforeEach(function(){
            initMapArea();
        });

        it('should get instanciated', function() {
            expect(d3.select("#mapArea svg").empty()).toBeFalsy();
        });

        it('should load', function() {
            showBgMap("backgroundMap", getJSONFixture("test/phpunit/_data/countries.json"));
            expect(d3.select("#backgroundMap").empty()).toBeFalsy();
        })
    });

    describe('External map', function() {
        var territoryName = 'simple';
        var territoryFilename = 'simple.svg';

        beforeEach(function(){

            jasmine.getFixtures().fixturesPath = 'base';
            helperStepsData = [{
                step: 1, content: ['Step 1 description'],
                dataUpdate: function() {},
                buttons: []
            }];

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

        it('should load', function() {
            loadRandomTerritoryMap();

            var bgWidth = width;
            var bgHeight = mapHeight;

            var svgWidth = 110;
            var svgHeight = 60;

            expect(svgMap).toBeDefined();
            expect(svgMap.attr('name')).toEqual(territoryFilename);
            expect(svgMap.datum().x).toEqual((bgWidth - svgWidth) /2);
            expect(svgMap.datum().y).toEqual((bgHeight - svgHeight) /2);
            expect(svgMap.datum().width).toEqual(svgWidth);
            expect(svgMap.datum().height).toEqual(svgHeight);
            expect(svgMap.style("margin-left")).toEqual((bgWidth - svgWidth) /2 + 'px');
            expect(svgMap.style("margin-top")).toEqual((bgHeight - svgHeight) /2 + 'px');
        });
    });
});