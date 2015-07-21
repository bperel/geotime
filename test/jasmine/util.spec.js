describe('Util tests', function() {

    beforeEach(function() {
        jasmine.getFixtures().fixturesPath = 'base';
        loadFixtures("map-placeholders.html");
    });

    describe('styleIntWithoutPx', function() {
        it('should return an integer', function() {
            d3.select('body').append('div').attr('id', 'styleGetterTest').style('height', '15px');
            expect(d3.select('#styleGetterTest').styleIntWithoutPx('height')).toEqual(15);
        });

        it('should return null if no property is given', function() {
            d3.select('body').append('div').attr('id', 'styleGetterTest').style('height', '15px');
            expect(d3.select('#styleGetterTest').styleIntWithoutPx()).toEqual(null);
        });
    });

    describe('flattenArrayOfObjects', function() {
        it('should return the merged object', function() {
            var array = [
                {myObject: {a: 'b', c: 'd'}, otherObject: {a: 'b'}, lastObject: {'1': 2}},
                {myObject: {e: 'f', g: 'h'}, otherObject: {a: 'b', c: 'd'}}
            ];

            expect(flattenArrayOfObjects(array)).toEqual(
                {myObject: {a: 'b', c: 'd', e: 'f', g: 'h'}, otherObject: {a: 'b', c: 'd'}, lastObject: {'1': 2}}
            );
        });
    });

    describe('pathToCoordinates', function() {
        beforeEach(function(){
            setFixtures('<svg><path id="myPath" d="M0 0 L'+(width/2)+' '+(mapHeight/2)+ ' L'+width+' 0"></path>');
        });

        it('should return coordinates', function() {
            resizeExternalMap();

            var pathCoordinates = d3.select('#myPath').getPathCoordinates();
            var pathLength = 2 * Math.ceil(Math.sqrt(Math.pow(width/2, 2) + Math.pow(mapHeight/2, 2)));
            expect(pathCoordinates.length).toEqual(pathLength);
            expect(pathCoordinates[0]).toEqual(projection.invert([0, 0]));
        });
    });

    describe('round10pow', function() {
        it('should round a number using 2 decimals', function() {
            expect((123.456).round10pow(2)).toEqual(123.46);
        });
        it('should round a number using 0 decimals as default', function() {
            expect((123.456).round10pow()).toEqual(123);
        });
    })
});