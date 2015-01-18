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
			step: 1, content: ['Select 4 points on the maps.',
							   '<span id="selectedPointsLength"></span>&nbsp;<label for="selectedPointsLength">selected points.</label>'
							  +'<span id="selectedPoints"></span>'],
			onLoad: [enableCalibrationPointSelection],
			onUnload: [disableCalibrationPointSelection],
			dataUpdate: saveMapProjection,
			buttons: ['done', 'skip', 'cancel']
		}, {
			step: 2, content: ['Move the superimposed map so that it corresponds to the background borders.'],
			onLoad: [enableMapDragResize],
			onUnload: [disableMapDragResize],
			dataInit: initMapData,
			dataUpdate: saveMapPosition,
			buttons: ['done', 'skip', 'cancel']
		}, {
			step: 3, content: ['Select with your mouse a country whose name is written on the map or known by you.',
							   'Chosen territory : <span id="territoryId">None</span>'],
			onLoad: [enableTerritorySelection],
			onUnload: [disableTerritorySelection],
			dataUpdate: saveTerritoryPosition, validate: checkSelectedTerritory,
			buttons: ['done', 'skip', 'cancel']
		}, {
			step: 4, content: ['<label for="territoryName">Type in its name</label>',
							   '<input type="text" id="territoryName" />'],
			onLoad: [initTerritoryAutocomplete],
			dataUpdate: saveTerritoryName,
			buttons: ['done', 'skip', 'cancel']
		}, {
			step: 5, content: ['During what period did this territory have these borders ?<br />',
							   '<label for="territoryPeriodStart">From </label><input type="number" id="territoryPeriodStart" />'
							 + '<label for="territoryPeriodEnd"  > to  </label><input type="number" id="territoryPeriodEnd" />'],
			dataUpdate: saveTerritoryPeriod,
			buttons: ['done', 'cancel']
		}
	];
}