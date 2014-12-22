function loadHelperConfig() {
	helperButtonsData = [
		{
			name: 'done', cssClass: 'helperStepDone', text: 'Done !',
			click: activateHelperNextStep
		},
		{
			name: 'skip', cssClass: 'helperStepSkip', text: 'Skip this step',
			click: activateHelperNextStep
		},
		{
			name: 'cancel', cssClass: 'helperStepCancel', text: 'Switch to another map',
			click: ignoreCurrentMap
		}
	];

	helperStepsData = [
		{
			step: 1, content: ['Move the superimposed map so that it corresponds to the background borders.'],
			dataUpdate: saveMapPosition,
			buttons: ['done', 'skip', 'cancel']
		}, {
			step: 2, content: ['Select with your mouse a country whose name is written on the map or known by you.',
							   'Chosen territory : <span id="territoryId">None</span>'],
			dataUpdate: saveTerritoryPosition, validate: checkSelectedTerritory,
			buttons: ['done', 'skip', 'cancel']
		}, {
			step: 3, content: ['<label for="territoryName">Type in its name</label>',
							   '<input type="text" id="territoryName" />'],
			dataUpdate: saveTerritoryName,
			buttons: ['done', 'skip', 'cancel']
		}, {
			step: 4, content: ['During what period did this territory have these borders ?<br />',
							   '<label for="territoryPeriodStart">From </label><input type="number" id="territoryPeriodStart" />'
							 + '<label for="territoryPeriodEnd"  > to  </label><input type="number" id="territoryPeriodEnd" />'],
			dataUpdate: saveTerritoryPeriod,
			buttons: ['done', 'cancel']
		}
	];
}