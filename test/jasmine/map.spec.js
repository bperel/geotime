describe('Map tests', function() {
    beforeEach(function(){
        gatewayUrl = '../../gateway.php';

        jasmine.getJSONFixtures().fixturesPath = 'base';
        loadJSONFixtures("test/geotime/_data/countries.json");

        jasmine.getFixtures().fixturesPath = 'base';
        loadFixtures("map-placeholders.html");

        // then install the mock
        jasmine.Ajax.install();
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
            showBgMap("backgroundMap", getJSONFixture("test/geotime/_data/countries.json"));
            expect(d3.select("#backgroundMap").empty()).toBeFalsy();
        })
    })
});