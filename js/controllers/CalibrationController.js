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
			$scope.groupedCalibrationPointsNb = calibrationPoints.length;

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

		$scope.removeCalibrationPoint = function(calibrationPointIndex) {
			$scope.groupedCalibrationPoints.splice(calibrationPointIndex, 1);
			$scope.groupedCalibrationPointsNb = calibrationPoints.length;
			$scope.loadCalibrationPoints($scope.groupedCalibrationPoints);
		};

		enableCalibrationPointSelection();
		showMapsSideBySide();

		$rootScope.$broadcast('toggleMapDragZoom', { toggle: true });

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