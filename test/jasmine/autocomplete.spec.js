var territoryName;

describe('Autocomplete tests', function() {

    beforeEach(function(){
        setFixtures('<input type="text" id="territoryName" />');
        territoryName = d3.select('#territoryName');
    });

    describe('Autocomplete field creation', function() {

        it('should be created', function () {
            autocomplete(d3.select('#territoryName').node())
                .dataField("name");
            expect(d3.select('.bp-autocomplete-dropdown')).toExist();
        });
    });

    describe('Autocomplete field search', function() {

        beforeEach(function() {
            autocomplete(territoryName.node())
                .dataField("name")
                .width(960)
                .height(500)
                .render();
        });

        it('should return values', function () {

            spyOn(window, "ajaxPost").and.callFake(function(options, callback) {
                callback('', [{id: 'afg', name: 'Afghanistan'}, {id: 'arm', name: 'Armenia'}]);
            });

            territoryName
                .attr('value', 'A')
                .on('keyup')();

            expect(d3.selectAll('.bp-autocomplete-title:not(:empty)').size()).toEqual(2);
            expect(d3.select('.bp-autocomplete-title:not(:empty)').text()).toEqual('Afghanistan');

            territoryName
                .attr('value', 'Ar')
                .on('keyup')();

            expect(d3.selectAll('.bp-autocomplete-title:not(:empty)').size()).toEqual(1);
            expect(d3.select('.bp-autocomplete-title:not(:empty)').text()).toEqual('Armenia');

            territoryName
                .attr('value', 'Arg')
                .on('keyup')();

            expect(d3.selectAll('.bp-autocomplete-title:not(:empty)').size()).toEqual(0);
        });

        it('should show a message if no result is available', function () {

            spyOn(window, "ajaxPost").and.callFake(function(options, callback) {
                callback('', []);
            });

            territoryName
                .attr('value', '')
                .on('keyup')();

            expect(d3.selectAll('.bp-autocomplete-title:not(:empty)').size()).toEqual(0);
            expect(d3.select('.bp-autocomplete-searching').text()).toEqual('No results');

            territoryName
                .attr('value', 'B')
                .on('keyup')();

            expect(d3.selectAll('.bp-autocomplete-title:not(:empty)').size()).toEqual(0);
            expect(d3.select('.bp-autocomplete-searching').text()).toEqual('No results');

        });
    });

    describe('Autocomplete field result selection', function() {

        beforeEach(function() {
            autocomplete(territoryName.node())
                .dataField("name")
                .width(960)
                .height(500)
                .render();

            spyOn(window, "ajaxPost").and.callFake(function(options, callback) {
                callback('', [{id: 'afg', name: 'Afghanistan'}, {id: 'arm', name: 'Armenia'}]);
            });
        });

        it('should change the input value when clicking on a result', function() {
            territoryName
                .attr('value', 'A')
                .on('keyup')();

            var firstResult = d3.select('.bp-autocomplete-row:not(:empty)');

            firstResult.on('click')(firstResult.datum());

            expect(d3.select('.bp-autocomplete-dropdown').style('display')).toEqual('none');
            expect(territoryName.datum().territoryId).toEqual('afg');
            expect(territoryName.node().value).toEqual('Afghanistan');
        });
    });
});