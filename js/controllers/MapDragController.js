geotimeControllers.controller('MapDragController', ['$scope',
	function($scope) {
		$scope.scale = (widthSideBySide - 1) / 2 / Math.PI;

		$scope.zoom = d3.behavior.zoom()
			.scale($scope.scale)
			.scaleExtent([$scope.scale, 16 * $scope.scale])
			.on("zoom", function() {
				d3.event.sourceEvent.stopPropagation();

				if ($scope.dragMode === 'pan') {
					projection.translate($scope.zoom.translate());
				}
				else {
					projection.rotate([lambda(d3.event.translate[0]), phi(d3.event.translate[1])]);
				}

				projection.scale($scope.zoom.scale());
				drawPaths();
			});
		
		$scope.showDragActions = false;

		$scope.dragActions = [
			{name: 'pan', text: 'Pan on drag'},
			{name: 'rotate', text: 'Rotate on drag'}
		];
		$scope.dragMode = 'pan';

		$scope.$on('toggleMapDragZoom', function(event, args) {
			$scope.showDragActions = args.toggle;

			if (args.toggle) {
				svg.call($scope.zoom);
			}
			else {
				svg.on('mousedown.drag', null);
			}
		});
	}]);
