geotimeControllers.controller('MapCalibrationController', ['$scope',
	function($scope) {
		$scope.loadCalibrationPoints($scope.getMapInfo().calibrationPoints);
	}
]);

geotimeControllers.controller('CalibrationController', ['$scope', '$rootScope', '$state',
	function($scope, $rootScope, $state) {

		$scope.getMapInfo = function() {
			return $scope.$parent.mapInfo;
		};

		$scope.calibrationPointTypes = [{
			text: 'Background point',
			property: 'bgPoint',
			buttonClass: 'btn-info'
		}, {
			text: 'Foreground point',
			property: 'fgPoint',
			buttonClass: 'btn-primary'
		}];

		$scope.loadCalibrationPoints = function(calibrationPoints) {
			$scope.calibrationPoints = [];
			$scope.groupedCalibrationPoints = calibrationPoints;

			if (calibrationPoints) {
				angular.forEach(calibrationPoints, function(calibrationPoint) {
					addCalibrationMarker("fgPoint", calibrationPoint.fgPoint, false);
					addCalibrationMarker("bgPoint", calibrationPoint.bgPoint, false);
				});
			}
			markersSvg
				.repositionCalibrationMarkers($scope.calibrationPoints)
				.classed('hidden', false);

			// $scope.groupedCalibrationPoints = $scope.getGroupedCalibrationPoints();
		};

		$scope.calibrationPointToString = function(calibrationPoint, property) {
			return JSON.stringify(calibrationPoint[property]);
		};

		$scope.getGroupedCalibrationPoints = function(withProjectedCoords) {
			var shownCalibrationPoints = {};
			$scope.calibrationPoints.forEach(function(d) {
				if (!(shownCalibrationPoints[d.pointId])) {
					shownCalibrationPoints[d.pointId] = {
						pointId: d.pointId
					};
				}
				shownCalibrationPoints[d.pointId][d.type] = d.coordinates;
				if (d.type === 'bgPoint' && !withProjectedCoords) {
					delete shownCalibrationPoints[d.pointId][d.type].x;
					delete shownCalibrationPoints[d.pointId][d.type].y;
				}
			});

			return d3.values(shownCalibrationPoints);
		};
		
		$scope.updateGroupedCalibrationPoints = function() {
			$scope.groupedCalibrationPoints = getGroupedCalibrationPoints();
		};

		$scope.removeCalibrationPoint = function(calibrationPointIndex) {
			var toDelete = [];
			$scope.calibrationPoints.forEach(function(calibrationPoint, i) {
				if (calibrationPoint.pointId === calibrationPointIndex) {
					toDelete.push(i);
				}
			});
			toDelete.reverse().forEach(function(calibrationPointIndex) {
				$scope.calibrationPoints.splice(calibrationPointIndex, 1);
			});

			markersSvg.repositionCalibrationMarkers($scope.calibrationPoints);

			$scope.updateGroupedCalibrationPoints();
		};

		enableCalibrationPointSelection();

		$rootScope.$broadcast('toggleBgMap', { toggle: true });
		$rootScope.$broadcast('toggleMapDragZoom', { toggle: true });
		$rootScope.$broadcast('positionExternalMap', { sideBySide: true });

		$scope.$on('$destroy', function() {
			$rootScope.$broadcast('toggleMapDragZoom', { toggle: false });
			disableCalibrationPointSelection();
			unloadCalibrationPoints();
		});

		$state.go($state.current.name+'.selectCalibrationPoints');

		$scope.processForm = function() {
			alert('Submit');
		};
	}]
);