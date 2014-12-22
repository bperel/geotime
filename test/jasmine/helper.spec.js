var testHelperSteps;
var testButtonData;

describe('Helper tests', function() {
    function testDataUpdate() {

    }

    function clickOnSkipTest() {

    }

    function clickOnDoneTest() {

    }

    beforeEach(function(){
        jasmine.getFixtures().fixturesPath = 'base';
        loadFixtures("map-placeholders.html");

        testHelperSteps = [{
            step: 1, content: ['Step 1 description', 'This is shown when the step is active'],
            dataUpdate: testDataUpdate,
            buttons: ['done', 'skip']
        },{
            step: 2, content: ['Step 2 description'],
            buttons: ['done']
        }];

        testButtonData = [
            { name: 'done',   cssClass: 'helperStepDone', text: 'Done !', click: clickOnDoneTest },
            { name: 'skip',   cssClass: 'helperStepSkip', text: 'Skip', click: clickOnSkipTest }
        ];

        helperButtonsData = testButtonData;
    });

    describe('Helper init', function() {
        it('should load the helper', function() {
            initHelper('myMap.svg', testHelperSteps);

            expect(d3.select('#mapTitle').text()).toEqual('myMap.svg');
            expect(d3.select('#resizeHandle').classed('hidden')).toBeTruthy();
            expect(helperSteps.size()).toEqual(2);

            var firstStep = helperSteps.filter(function(d) { return d.step === 1; });
            var secondStep = helperSteps.filter(function(d) { return d.step === 2; });

            expect(firstStep.size()).toEqual(1);
            expect(firstStep.text()).toEqual('Step 1 descriptionThis is shown when the step is active');
            expect(firstStep.select('.if-active').text()).toEqual('This is shown when the step is active');

            expect(secondStep.size()).toEqual(1);
            expect(secondStep.text()).toEqual('Step 2 description');
            expect(secondStep.select('.if-active').text()).toEqual('');
        });

        it('should load a helper step', function() {
            initHelper('myMap.svg', testHelperSteps);
            activateHelperNextStep();

            var firstStep = helperSteps.filter(function(d) { return d.step === 1; });
            var secondStep = helperSteps.filter(function(d) { return d.step === 2; });

            expect(firstStep.classed('active')).toBeTruthy();
            var firstStepButtons = firstStep.selectAll('button:not(.hidden)');
            expect(firstStepButtons.size()).toEqual(2);
            expect(firstStepButtons.classed('helperStepDone')).toBeTruthy();
            expect(firstStepButtons.text()).toEqual('Done !');

            expect(secondStep.classed('active')).toBeFalsy();

            activateHelperNextStep();

            expect(firstStep.classed('active')).toBeFalsy();

            expect(secondStep.classed('active')).toBeTruthy();
            expect(secondStep.selectAll('button:not(.hidden)').size()).toEqual(1);
        });
    });
});