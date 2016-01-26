var geotimeApp = angular.module('geotimeApp', [
	'ngRoute',
	'geotimeControllers'
]);

geotimeApp.config(['$routeProvider',
	function($routeProvider) {
		$routeProvider.
		when('/map-placeholders', {
			templateUrl: 'map-placeholders.html',
			controller: 'MapController'
		}).
		otherwise({
			redirectTo: '/map-placeholders'
		});
	}
]);