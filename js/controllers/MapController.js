geotimeControllers.controller('MapController', ['$scope',
	function($scope) {

		$scope.resizeBackgroundMap = function(width, height) {
			$scope.maps.background.width = width;
			$scope.maps.background.height = height;
		};

		$scope.$on('toggleBgMap', function(event, args) {
			$scope.maps.background.show = args.toggle;
		});

		$scope.initForegroundMap = function() {
			$scope.maps.foreground.width = svgMap.attrIntWithoutPx("width");
			$scope.maps.foreground.height = svgMap.attrIntWithoutPx("height");

			var element = angular.element(svgMap.node());
			svgMap
				.attr("ng-style",
					"{" +
						"width: maps.foreground.width +'px'," +
						"height: maps.foreground.height +'px'" +
					"}")
				.attr("viewBox", [0, 0, $scope.maps.foreground.width, $scope.maps.foreground.height].join(' '));

			element.injector().invoke(function($compile){
				$compile(element)($scope);
			});
			$scope.$apply();
		};

		$scope.resizeForegroundMap = function(forcedWidth, forcedHeight) {
			if (forcedWidth) {
				var foregroundMapOriginalRatio = $scope.maps.foreground.width / $scope.maps.foreground.height;
				var foregroundMapCurrentRatio = forcedWidth / forcedHeight;
				if (foregroundMapCurrentRatio > foregroundMapOriginalRatio) {
					forcedWidth = forcedHeight * foregroundMapOriginalRatio;
				}
				else if (foregroundMapCurrentRatio < foregroundMapOriginalRatio) {
					forcedHeight = forcedWidth / foregroundMapOriginalRatio;
				}
			}
			else { // Auto fit
				var widthRatio = $scope.maps.background.width / $scope.maps.foreground.width;
				var heightRatio = $scope.maps.background.height / $scope.maps.foreground.height;

				if (widthRatio < heightRatio) {
					forcedWidth = $scope.maps.background.width * (maxExternalMapSizePercentage / 100);
					forcedHeight = $scope.maps.foreground.height / ($scope.maps.foreground.width / forcedWidth);
				}
				else {
					forcedHeight = $scope.maps.background.height * (maxExternalMapSizePercentage / 100);
					forcedWidth = $scope.maps.foreground.width / ($scope.maps.foreground.height / forcedHeight);
				}
			}
			$scope.maps.foreground.width  = forcedWidth;
			$scope.maps.foreground.height = forcedHeight;
		};


		$scope.maps = {
			background: {
				show: true,
				width: null,
				height: null
			},
			foreground: {
				show: true,
				width: null,
				height: null
			}
		};

		$scope.showBgMap = true;

		initBackgroundMap();
		$scope.resizeBackgroundMap(widthSuperimposed, mapHeight);

		getAndShowBgMap("backgroundMap", "data/external/ne_50m_coastline.json", function() {
			applyCurrentProjection();
		});
	}]);
