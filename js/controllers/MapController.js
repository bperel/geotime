geotimeControllers.controller('MapController', ['$scope', '$rootScope',
	function($scope, $rootScope) {

		$scope.resizeBackgroundMap = function(width, height) {
			$scope.maps.background.width = width;
			$scope.maps.background.height = height;
		};

		$scope.getForegroundMapMarkerOffsetTransform = function() {
			return 'translate(' + [ $scope.maps.foreground.margin.left, $scope.maps.foreground.margin.top ].join(', ') + ')';
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
						"width:"  	  + "maps.foreground.width"       +"+'px'," +
						"height:" 	  + "maps.foreground.height"      +"+'px'," +
						"marginLeft:" + "maps.foreground.margin.left" +"+'px'," +
						"marginTop:"  + "maps.foreground.margin.top"  +"+'px'" +
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

		$scope.$on('positionExternalMap', function(event, args) {
			svgMap.classed('semi-transparent', !args.sideBySide && !!$rootScope.mapInfo.projection);


			$scope.maps.background.width = args.sideBySide ? widthSideBySide : widthSuperimposed;
			$scope.maps.background.height = mapHeight;
			
			$scope.maps.foreground.margin = {
				left: args.sideBySide 
					? $scope.maps.background.width
					: ($scope.maps.background.width  - $scope.maps.foreground.width ) / 2,
				top: args.sideBySide 
					? 0
					: ($scope.maps.background.height - $scope.maps.foreground.height) / 2
			};
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
				height: null,
				margin: {
					left: 0,
					top: 0
				}
			}
		};

		$scope.showBgMap = true;

		initBackgroundMap();
		$scope.resizeBackgroundMap(widthSuperimposed, mapHeight);

		getAndShowBgMap("backgroundMap", "data/external/ne_50m_coastline.json", function() {
			applyCurrentProjection();
		});
	}]);
