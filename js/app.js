var geotimeApp = angular.module('geotimeApp', [
	'ngRoute',
	'geotimeControllers'
]);

geotimeApp.config(['$routeProvider',
	function($routeProvider) {
		$routeProvider
			.when('/map-placeholders', {
				templateUrl: 'map-placeholders.html',
				controller: 'MapController'
			})
			.otherwise({
				redirectTo: '/map-placeholders'
			});
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