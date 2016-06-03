geotimeControllers.controller('CalibrationMarkerController', ['$scope',
	function($scope) {
		markersSvg = d3.select("#markers");
		
		$scope.markerSide = 9;
		$scope.markerCircleRadius = $scope.markerSide * 2 / 3;
		
		$scope.circles = [{
			color: '#000000'
		}, {
			color: 'inherit'
		}];
		
		$scope.lines = [
			{id: 'up', 	  d: 'M'+[$scope.markerSide, $scope.markerCircleRadius  ].join(',')+' L'+[$scope.markerSide, 0           		].join(',')+' z'},
			{id: 'down',  d: 'M'+[$scope.markerSide, $scope.markerCircleRadius*2].join(',')+' L'+[$scope.markerSide, $scope.markerSide*2].join(',')+' z'},
			{id: 'left',  d: 'M'+[$scope.markerCircleRadius, $scope.markerSide  ].join(',')+' L'+[0, $scope.markerSide           		].join(',')+' z'},
			{id: 'right', d: 'M'+[$scope.markerCircleRadius*2, $scope.markerSide].join(',')+' L'+[$scope.markerSide*2, $scope.markerSide].join(',')+' z'}
		];
	}]
);