define(function (require) {

	var angular = require('angular');
	require("ngRoute");

	var geotimeApp = angular.module('geotimeApp', [
		'geotimeControllers'
	]);

	geotimeApp.init = function (angular) {
		angular.bootstrap(document.documentElement, ['geotimeApp']);
	};

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

	geotimeApp.run(function ($window, auth, user) {
		auth.setAuthorizationHeaders();
		user.initialize();
	});

	return geotimeApp;

});