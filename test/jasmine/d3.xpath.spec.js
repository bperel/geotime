describe('XPath tests', function() {

    beforeEach(function(){
        setFixtures('<svg id="externalSvg"><path id="myPath"></path><path name="myNamedPath"></path></svg>');
    });

    describe('XPath generation', function() {
        it('should handle IDs', function() {
            expect(d3.select('#myPath').xpathForSvgChild()).toEqual('//svg:path[@id="myPath"]');
        });

        it('should calculate XPath relative to an element', function() {
            expect(d3.select('[name="myNamedPath"]').xpathForSvgChild()).toEqual('/svg:svg/svg:path[2]');
        });
    });
});