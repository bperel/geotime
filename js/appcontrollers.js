var geotimeControllers = angular.module('geotimeControllers', []);

geotimeControllers.controller('MapController', ['$scope', '$http',
	function($scope) {
		$scope.loadTerritoryMap = function(noUi) {
			loadTerritoryMap(noUi, $scope.mapFileName)
		};

		initBackgroundMap();
		resizeBackgroundMap(widthSuperimposed, mapHeight);

		loadUI();
		loadHelperConfig();
		getAndShowBgMap("backgroundMap", "data/external/ne_50m_coastline.json", function() {
			applyCurrentProjection();
			loadMaps();
		});
	}]);