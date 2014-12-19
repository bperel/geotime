describe('Autocomplete tests', function() {
    beforeEach(function(){
        setFixtures('<input type="text" id="territoryName" />');
    });

    describe('Autocomplete field', function() {
        it('should be created', function() {
            autocomplete(d3.select('#territoryName').node())
                .dataField("name");
            expect(d3.select('.bp-autocomplete-dropdown')).toExist();
        });

        it('should return values', function() {
            spyOn(window, "ajaxPost").and.callFake(function(options, callback) {
                callback('', [{name: 'Afghanistan'}, {name: 'Armenia'}]);
            });

            var territoryName = d3.select('#territoryName');
            autocomplete(territoryName.node())
                .dataField("name")
                .width(960)
                .height(500)
                .render();

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
        })
    });
});