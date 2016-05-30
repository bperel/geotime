geotimeControllers.controller('HelperController', ['$scope', '$state',
	function($scope, $state) {

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
	}]);
