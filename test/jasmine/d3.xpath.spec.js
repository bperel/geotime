describe('XPath tests', function() {

    beforeEach(function(){
        setFixtures('<svg><path id="myPath"></path><path name="myNamedPath"></path>');
    });

    describe('XPath generation', function() {
        it('should handle simple HTML tags', function() {
            expect(d3.select('body').xpathForSvgChild()).toEqual('/html/body');
        });

        it('should handle IDs', function() {
            expect(d3.select('#myPath').xpathForSvgChild(d3.select('svg'))).toEqual('//path[id="myPath"]');
            expect(d3.select('#myPath').xpathForSvgChild()).toEqual('//path[id="myPath"]');
        });

        it('should calculate XPath relative to an element', function() {
            expect(d3.select('[name="myNamedPath"]').xpathForSvgChild()).toEqual('/html/body/div/svg/path[2]');
        });
    });
});