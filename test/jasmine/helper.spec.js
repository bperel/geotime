var testHelperProcesses;
var testHelperSteps;
var testButtons;

var testValue;

describe('Helper tests', function() {
    var callbacks = null;

    beforeEach(function(){
        jasmine.getFixtures().fixturesPath = 'base';

        templateRoot = 'test/jasmine/templates/';

        loadFixtures("map-placeholders.html");

        callbacks = {
            myOnLoad: function() {
                testValue = 'loaded';
            },
            myOnUnload: function() {
                testValue+= 'unloaded';
            },
            dataUpdate: function () {
                return function (d) {
                    d.newData = 'myValue';
                    return d;
                };
            },
            clickOnDoneTest: function () { }
        };

        testHelperProcesses = [
            {
                name: 'process-1',
                text: 'Process 1'
            }
        ];

        testHelperSteps = [{
            process: 'process-1',
            order: 1, step: 'step-1',
            onLoad: [callbacks.myOnLoad],
            onUnload: [callbacks.myOnUnload],
            dataUpdate: callbacks.dataUpdate,
            buttons: ['done', 'skip']
        },{
            process: 'process-1',
            order: 2, step: 'step-2',
            buttons: ['done']
        }];

        testButtons = [
            { name: 'done',   cssClass: 'helperStepDone', text: 'Done !', click: callbacks.clickOnDoneTest }
        ];

        helperButtonsData = testButtons;
        helperProcessesData = testHelperProcesses;
        helperStepsData = testHelperSteps;

        testValue = null;

        spyOn(d3, 'text').and.callFake(function(path, callback) {
            callback(null, jasmine.getFixtures().read(path));
        });
    });

    describe('Helper behaviour', function() {
        it('should load the helper', function() {
            initHelper('process-1');

            expect(helperStepsForProcess.size()).toEqual(2);

            var firstStep = helperStepsForProcess.filter(function(d) { return d.order === 1; });
            var secondStep = helperStepsForProcess.filter(function(d) { return d.order === 2; });

            var step1TemplateContent = jasmine.getFixtures().read(templateRoot+'process-1/step-1.html');
            expect(firstStep.size()).toEqual(1);
            expect(firstStep.text()).toEqual(step1TemplateContent + 'Done !');
            expect(firstStep.select('.if-active').text()).toEqual(step1TemplateContent);

            var step2TemplateContent = jasmine.getFixtures().read(templateRoot+'process-1/step-2.html');
            expect(secondStep.size()).toEqual(1);
            expect(secondStep.text()).toEqual(step2TemplateContent);
            expect(secondStep.select('.if-active').text()).toEqual(step2TemplateContent);

            expect(testValue).toEqual(null);
        });

        it('should load a helper step', function() {
            initHelper(testHelperSteps);

            expect(testValue).toEqual(null);
            activateHelperNextStep();
            expect(testValue).toEqual('loaded');

            var firstStep = helperStepsForProcess.filter(function(d) { return d.order === 1; });
            var secondStep = helperStepsForProcess.filter(function(d) { return d.order === 2; });

            expect(firstStep.classed('active')).toBeTruthy();
            var firstStepButtons = firstStep.selectAll('button:not(.hidden)');
            expect(firstStepButtons.size()).toEqual(2);
            expect(firstStepButtons.classed('helperStepDone')).toBeTruthy();
            expect(firstStepButtons.text()).toEqual('Done !');

            expect(secondStep.classed('active')).toBeFalsy();

            activateHelperNextStep();
            expect(testValue).toContain('unloaded');

            expect(firstStep.classed('active')).toBeFalsy();

            expect(secondStep.classed('active')).toBeTruthy();
            expect(secondStep.selectAll('button:not(.hidden)').size()).toEqual(1);
        });

        it('should execute an action on a button click', function() {
            initHelper(testHelperSteps);
            activateHelperNextStep();

            var firstStep = helperStepsForProcess.filter(function(d) { return d.order === 1; });

            // Click on the 'done' button
            var doneButton = firstStep.selectAll('button').filter(function(d) { return d.name === 'done'; });

            spyOn(doneButton.datum(), 'click');
            doneButton.on('click')(doneButton.datum());

            expect(firstStep.datum().newData).toEqual('myValue');
            expect(doneButton.datum().click).toHaveBeenCalled();

            // Click on the 'skip' button. No data is updated
            var skipButton = firstStep.selectAll('button').filter(function(d) { return d.name === 'skip'; });

            spyOn(firstStep.datum(), 'dataUpdate');
            spyOn(skipButton.datum(), 'click');
            skipButton.on('click')(skipButton.datum());

            expect(firstStep.datum().dataUpdate).not.toHaveBeenCalled();
            expect(skipButton.datum().click).toHaveBeenCalled();
        });
    });
});