geotimeControllers.controller('MapController', ['$scope',
	function($scope) {

		$scope.resizeBackgroundMap = function(width, height) {
			$scope.maps.background.width = width;
			$scope.maps.background.height = height;
		};

		$scope.$on('toggleBgMap', function(event, args) {
			$scope.maps.background.show = args.toggle;
		});

		$scope.$on('initForegroundMap', function() {
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
		});

		$scope.$on('resizeForegroundMap', function(event, args) {
			var width = args.width,
				height = args.height;

			if (width) {
				var foregroundMapOriginalRatio = $scope.maps.foreground.width / $scope.maps.foreground.height;
				var foregroundMapCurrentRatio = width / height;
				if (foregroundMapCurrentRatio > foregroundMapOriginalRatio) {
					width = height * foregroundMapOriginalRatio;
				}
				else if (foregroundMapCurrentRatio < foregroundMapOriginalRatio) {
					height = width / foregroundMapOriginalRatio;
				}
			}
			else { // Auto fit
				var widthRatio = $scope.maps.background.width / $scope.maps.foreground.width;
				var heightRatio = $scope.maps.background.height / $scope.maps.foreground.height;

				if (widthRatio < heightRatio) {
					width = $scope.maps.background.width * (maxExternalMapSizePercentage / 100);
					height = $scope.maps.foreground.height / ($scope.maps.foreground.width / width);
				}
				else {
					height = $scope.maps.background.height * (maxExternalMapSizePercentage / 100);
					width = $scope.maps.foreground.width / ($scope.maps.foreground.height / height);
				}
			}
			$scope.maps.foreground.width  = width;
			$scope.maps.foreground.height = height;
		});


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
