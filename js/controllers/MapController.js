var geotimeControllers = angular.module('geotimeControllers', []);

geotimeControllers.controller('MapController', ['$scope', '$state',
	function($scope, $state) {
		$scope.mapInfo = null;

		$scope.dragActions = [
			{name: 'pan', text: 'Pan on drag'},
			{name: 'rotate', text: 'Rotate on drag'}
		];
		$scope.dragMode = 'pan';
		
		$scope.processes = [];
		$scope.activeProcess = null;
		
		$scope.resizeBackgroundMap = function(width, height) {
			$scope.bgMapWidth = width;
			$scope.bgMapHeight = height;
		};

		$scope.loadProcess = function(processName) {
			$scope.activeProcess = processName;
			$state.go('app.map-placeholders.'+processName);
		};

		$scope.loadTerritoryMap = function(noUi) {
			if (!isLoading) {
				isLoading = true;
				ajaxPost(
					{ getSvg: 1, fileName: $scope.mapFileName },
					function(error, incompleteMapInfo) {
						if (!!incompleteMapInfo) {
							loadTerritoryMapData(incompleteMapInfo.fileName, incompleteMapInfo, false, noUi ? function() {} : function(mapInfo) {
								$scope.mapInfo = mapInfo;
								loadUIConfig($scope.mapInfo);
								$scope.processes = helperProcessesData;

								if ($scope.mapInfo.projection || $scope.mapInfo.territories.length) {
									$scope.loadProcess('territoryIdentification');
								}
								else {
									$scope.loadProcess('mapLocation');
								}
								$scope.$apply();
							});
						}
						isLoading = false;
					}
				);
			}
		};

		initBackgroundMap();
		$scope.resizeBackgroundMap(widthSuperimposed, mapHeight);

		loadUI();
		loadHelperConfig();
		getAndShowBgMap("backgroundMap", "data/external/ne_50m_coastline.json", function() {
			applyCurrentProjection();
			loadMaps();
		});
	}]);
