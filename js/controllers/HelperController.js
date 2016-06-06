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
		
		$scope.insertForegroundMap = function(foregroundMapSvg) {
			svgMap = d3.select(d3.select("#mapArea").node().insertBefore(foregroundMapSvg, svg.node()));
		};
		
		$scope.loadTerritoryMapFromSvgElement = function() {
			$rootScope.$broadcast('initForegroundMap');
			$rootScope.$broadcast('resizeForegroundMap', {
				width: svg.attrIntWithoutPx('width'),
				height: svg.attrIntWithoutPx('height')
			});

			loadTerritoryMapFromSvgElement($rootScope.mapInfo);
		};

		$scope.loadTerritoryMapData = function (mapInfo, contentFromFileSystem, callback) {
			if (mapInfo && mapInfo.fileName) {
				if (!svgMap || $rootScope.mapInfo.fileName !== mapInfo.fileName) {
					initExternalSvgMap(mapInfo.fileName);
					if (!!contentFromFileSystem) {
						var svgWrapper = document.createElement('div');
						svgWrapper.innerHTML = contentFromFileSystem;
						$scope.insertForegroundMap(d3.select(svgWrapper).select('svg').node());
						
						$scope.loadTerritoryMapFromSvgElement();
						loadExternalMapPosition(getExternalMapOffsetToCenter());
						
						return callback(mapInfo);
					}
					else {
						d3.xml("cache/svg/" + mapInfo.fileName, "image/svg+xml", function (svgDocument) {
							$scope.insertForegroundMap(document.importNode(svgDocument.documentElement, true));
							$scope.loadTerritoryMapFromSvgElement();
							
							callback(mapInfo);
						});
					}
				}
			}
		};

		$scope.loadTerritoryMap = function(noUi) {
			if (!isLoading) {
				isLoading = true;
				ajaxPost(
					{ getSvg: 1, fileName: $scope.currentMap.fileName },
					function(error, incompleteMapInfo) {
						if (!!incompleteMapInfo) {
							$rootScope.mapInfo = incompleteMapInfo;

							$scope.loadTerritoryMapData(
								incompleteMapInfo,
								false,
								noUi
									? function () {}
									: function () {
										loadUIConfig($rootScope.mapInfo);

										if ($rootScope.mapInfo.projection || $rootScope.mapInfo.territories.length) {
											$scope.loadProcess('territoryIdentification');
										}
										else {
											$scope.loadProcess('mapLocation');
										}
										$scope.$apply();
									}
							);
						}
						isLoading = false;
					}
				);
			}
		};

		$scope.loadMaps();

	}]);
