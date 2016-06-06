geotimeControllers.controller('HelperController', ['$scope', '$rootScope', '$state',
	function($scope, $rootScope, $state) {

		$scope.processes = [
			{
				name: 'mapLocation',
				text: 'Map location'
			}, {
				name: 'territoryIdentification',
				text: 'Territory identification'
			}
		];

		$scope.activeProcess = null;

		$scope.loadMaps = function() {
			ajaxPost(
				{ getMaps: 1 },
				function(error, maps) {
					maps.unshift({
						fileName: null,
						label: 'Select a map'
					});

					$scope.maps = maps;
					$scope.currentMap = $scope.maps[0];

					$scope.$apply();
				}
			);
		};

		$scope.loadProcess = function(processName) {
			$scope.activeProcess = processName;
			$state.go('app.map-placeholders.'+processName);
		};

		$scope.loadTerritoryMap = function(noUi) {
			if (!isLoading) {
				isLoading = true;
				ajaxPost(
					{ getSvg: 1, fileName: $scope.currentMap.fileName },
					function(error, incompleteMapInfo) {
						if (!!incompleteMapInfo) {
							loadTerritoryMapData(incompleteMapInfo.fileName, incompleteMapInfo, false, noUi ? function() {} : function(mapInfo) {
								$rootScope.mapInfo = mapInfo;
								loadUIConfig($rootScope.mapInfo);

								if ($rootScope.mapInfo.projection || $rootScope.mapInfo.territories.length) {
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

		$scope.loadMaps();

	}]);
