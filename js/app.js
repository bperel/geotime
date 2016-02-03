var geotimeApp = angular.module('geotimeApp', [
	'ui.router',
	'geotimeControllers'
]);

geotimeApp.config(['$stateProvider', '$urlRouterProvider',
	function($stateProvider, $urlRouterProvider) {
		$stateProvider
			.state('map-placeholders', {
				url: '/map-placeholders',
				templateUrl: 'map-placeholders.html',
				controller: 'MapController'
			})
			.state('map-placeholders.mapLocation', {
				url: '/mapLocation',
				templateUrl: 'templates/mapLocation/select-calibration-points.html',
				controller: 'MapLocationController'
			})
			.state('map-placeholders.territoryIdentification', {
				url: '/territoryIdentification',
				templateUrl: 'templates/territoryIdentification/locate-territories.html',
				controller: 'TerritoryIdentificationController'
			});

		$urlRouterProvider.otherwise('/map-placeholders');
	}
]);

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