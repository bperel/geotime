geotimeControllers.controller('MapController', ['$scope',
	function($scope) {
		$scope.mapInfo = null;
		
		$scope.resizeBackgroundMap = function(width, height) {
			$scope.bgMapWidth = width;
			$scope.bgMapHeight = height;
		};

		initBackgroundMap();
		$scope.resizeBackgroundMap(widthSuperimposed, mapHeight);

		loadUI();
		getAndShowBgMap("backgroundMap", "data/external/ne_50m_coastline.json", function() {
			applyCurrentProjection();
			loadMaps();
		});
	}]);
