geotimeControllers.controller('MapDragController', ['$scope',
	function($scope) {
		$scope.showDragActions = false;

		$scope.dragActions = [
			{name: 'pan', text: 'Pan on drag'},
			{name: 'rotate', text: 'Rotate on drag'}
		];
		$scope.dragMode = 'pan';

		$scope.$on('toggleMapDragZoom', function(event, args) {
			$scope.showDragActions = args.toggle;

			if (args.toggle) {
				svg.call(zoom);
			}
			else {
				svg.on('mousedown.drag', null);
			}
		});
	}]);
