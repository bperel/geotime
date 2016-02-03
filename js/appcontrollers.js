var geotimeControllers = angular.module('geotimeControllers', []);


geotimeControllers.controller('MapController', ['$scope', '$templateRequest',
	function($scope, $templateRequest) {
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
			angular.forEach(helperStepsData, function(step) {
				if ($scope.isActiveProcess(step.process)) {
					$scope.steps.push(step);
				}
			});
		};

		$scope.unloadCurrentStep = function() {
			((helperStepsData.filter($scope.isActiveStep)[0] || {})
				.onUnload || []
			)
			.forEach(function (onUnloadAction) {
				onUnloadAction();
			});
		};

		$scope.loadStepTemplate = function (args) {

			$templateRequest('templates/' + (args.process || '') + '/' + args.name +'.html').then(function(html){
				debugger;
			});
			/*
			var title             = args.title,
				callback          = args.callback,
				noConditionalShow = args.noConditionalShow,
				templatePath = 'templates/' + (args.process || '') + '/' + args.name +'.html';

			var element = this;
			var html =
				(title ? ('<h5>'+title+'</h5>') : '') +
				(noConditionalShow ? '[content]' : '<div class="if-active">[content]</div>');

			if (templates[templatePath]) {
				element.html(html.replace('[content]', templates[templatePath]));

				if (callback) {
					callback(element);
				}
			}
			else {
				d3.text(templatePath, function(error, templateHtml) {
					if (error) {
						console.log('Could not load template : '+templatePath);
					}
					else {
						element.html(
							html.replace('[content]', templates[templatePath] = templateHtml)
						);
						if (callback) {
							callback(element);
						}
					}
				});
			}*/

			return this;
		};

		$scope.loadProcess = function(processName) {
			$scope.unloadCurrentStep();
			$scope.activeProcess = processName;
			$scope.activeStep = 0;

			$scope.initSteps();

			return;

			angular.forEach($scope.steps, function(step) {
				var callback = step.process === processName && step.order === 1 ? activateHelperNextStep : noop;

				$scope.loadStepTemplate({
					name: step.step,
					process: step.process,
					callback: callback
				});

				//d3.select(this).loadTemplate({
				//	process: step.process,
				//	name: step.step,
				//	title: step.title,
				//	callback: callback
				//});
			});
		};

		$scope.loadTerritoryMap = function(noUi) {
			if (!isLoading) {
				isLoading = true;
				ajaxPost(
					{ getSvg: 1, fileName: $scope.mapFileName },
					function(error, incompleteMapInfo) {
						if (!!incompleteMapInfo) {
							loadTerritoryMapData(incompleteMapInfo.fileName, incompleteMapInfo, false, noUi ? function() {} : function(mapInfo) {
								loadUIConfig(mapInfo);
								$scope.processes = helperProcessesData;

								if (mapInfo.projection || mapInfo.territories.length) {
									$scope.loadProcess('territoryIdentification');
								}
								else {
									$scope.loadProcess('mapLocation');
								}
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
