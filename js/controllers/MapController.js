var geotimeControllers = angular.module('geotimeControllers', []);

geotimeControllers.controller('MapController', ['$scope', '$state',
	function($scope, $state) {
		$scope.mapInfo = null;

		$scope.processes = [];
		$scope.steps = helperStepsData;

		$scope.activeProcess = null;
		$scope.activeStep = null;

		$scope.resizeBackgroundMap = function(width, height) {
			$scope.bgMapWidth = width;
			$scope.bgMapHeight = height;
		};

		$scope.isActiveProcess = function(processName) {
			return processName === $scope.activeProcess;
		};

		$scope.isActiveStep = function (step) {
			return $scope.activeStep 	=== step.order
				&& $scope.activeProcess === step.process;
		};

		$scope.initSteps = function() {
			$scope.steps = [];
			// angular.forEach(helperStepsData, function(step) {
			// 	if ($scope.isActiveProcess(step.process)) {
			// 		$scope.steps.push(step);
			// 	}
			// });
		};

		$scope.unloadCurrentStep = function() {
			((helperStepsData.filter($scope.isActiveStep)[0] || {})
				.onUnload || []
			)
			.forEach(function (onUnloadAction) {
				onUnloadAction();
			});
		};

		$scope.loadProcess = function(processName) {
			$scope.unloadCurrentStep();
			$scope.activeProcess = processName;
			$scope.activeStep = 1;
			$state.go('app.map-placeholders.'+processName);

			$scope.initSteps();
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
