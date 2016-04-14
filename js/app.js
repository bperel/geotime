var geotimeApp = angular.module('geotimeApp', [
	'ui.router',
	'angucomplete-alt',
	'geotimeControllers'
]);

geotimeApp.config(['$stateProvider', '$urlRouterProvider',
	function($stateProvider, $urlRouterProvider) {

		$urlRouterProvider.otherwise('/');

		$stateProvider
			.state('app', {
				url: '/',
				views : {
					'content': {
						controller: 'MainController'
					}
				}
			})

			.state('app.map-placeholders', {
				url: '/map-placeholders',
				views: {
					'content@': {
						templateUrl: 'templates/map-placeholders.html'
					}
				}
			})

			.state('app.map-placeholders.mapLocation', {
				url: '/mapLocation',
				views: {
					'helperContent@app.map-placeholders': {
						templateUrl: 'templates/mapLocation/index.html',
						controller: 'CalibrationController'
					}
				}
			})
			.state('app.map-placeholders.mapLocation.selectCalibrationPoints', {
				url: '/mapLocation/selectCalibrationPoints',
				views: {
					'selectCalibrationPoints@app.map-placeholders.mapLocation': {
						templateUrl: 'templates/mapLocation/select-calibration-points.html',
						controller: 'MapCalibrationController'
					}
				}
			})
			.state('app.map-placeholders.mapLocation.adjust', {
				url: '/mapLocation/adjust',
				views: {
					'adjust@app.map-placeholders.mapLocation': {
						templateUrl: 'templates/mapLocation/adjust.html'
					}
				}
			})

			.state('app.map-placeholders.territoryIdentification', {
				url: '/territoryIdentification',
				views: {
					'helperContent@app.map-placeholders': {
						templateUrl: 'templates/territoryIdentification/index.html',
						controller: 'TerritoryIdentificationController'
					}
				}
			})
			.state('app.map-placeholders.territoryIdentification.locate', {
				url: '/territoryIdentification/locate',
				views: {
					'locate@app.map-placeholders.territoryIdentification': {
						templateUrl: 'templates/territoryIdentification/locate-territories.html'
					}
				}
			});
	}
]);

var geotimeControllers = angular.module('geotimeControllers', []);

geotimeControllers.controller('MainController', function($scope, $state) {
	$state.go('app.map-placeholders');
});

angular.forEach(['x', 'y', 'width', 'height'], function(name) {
	var ngName = 'ng' + name[0].toUpperCase() + name.slice(1);
	geotimeApp.directive(ngName, function() {
		return function(scope, element, attrs) {
			attrs.$observe(ngName, function(value) {
				attrs.$set(name, value);
			})
		};
	});
});