describe('XPath tests', function() {

    beforeEach(function(){
        setFixtures('<svg><path id="myPath"></path><path name="myNamedPath"></path>');
    });

    describe('XPath generation', function() {
        it('should handle simple HTML tags', function() {
            expect(d3.select('body').xpath()).toEqual('/html/body');
        });

        it('should handle IDs', function() {
            expect(d3.select('#myPath').xpath(d3.select('svg'))).toEqual('//path[id="myPath"]');
            expect(d3.select('#myPath').xpath()).toEqual('//path[id="myPath"]');
        });

        it('should calculate XPath relative to an element', function() {
            expect(d3.select('[name="myNamedPath"]').xpath()).toEqual('/html/body/div/svg/path[2]');
            expect(d3.select('[name="myNamedPath"]').xpath(d3.select('svg'))).toEqual('//path[2]');
        });
    });
});