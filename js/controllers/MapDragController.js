geotimeControllers.controller('MapDragController', ['$scope',
	function($scope) {
		$scope.dragActions = [
			{name: 'pan', text: 'Pan on drag'},
			{name: 'rotate', text: 'Rotate on drag'}
		];
		$scope.dragMode = 'pan';
	}]);
