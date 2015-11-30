
angular.module('PulpoLoco', ['ngRoute', 'ngResource'])
	.config(function($routeProvider, $locationProvider){
		$routeProvider
			.when('/', {
				action: 'home',
				controller: 'Home',
				templateUrl: 'home.html'
			})
			.when('/view/:id', {
				action: 'view',
				controller: 'View',
				templateUrl: 'view.html'
			})
			.otherwise({
				redirectTo: '/'
			});

		$locationProvider.html5Mode({
			enabled: true,
			requireBase: false
		});
	})

	.run(function($rootScope, $location) {

		$rootScope.back = function() {
			$location.path('/');
		};

		$rootScope.$on('submit', function(e, f) {
			$rootScope.error = null;
			$location.path('/view/' + f.uid);
		});

		$rootScope.$on('submit-error', function(e, msg) {
			$rootScope.error = msg ? msg.message : 'Could not save link';
		});

		$rootScope.loaded = true;
	})

	.service('LinkService', function($resource, $routeParams, $location, $rootScope) {

		var link = $resource('/submit', {}, {
			'submit': { 'method': 'POST', params : {}},
			'get': {
				'url': '/link/:id',
				'method': 'POST',
				params : {id: '@id'}
			}
		});

		this.get = function(id, success, fail) {
			var f = link.get({id: id}, function() {
				if (f.id) {
					success(f);
				} else {
					fail(f.message);
				}
			}, function() {
				fail('Could not get link');
			});
		}

		this.submit = function(url, permalink, success, fail) {
			link.submit({url: url, permalink, permalink}, function(f) {
				if (f.id) {
					success(f);
				} else {
					fail(f.message)
				}
			}, function() {
				fail('Could not save link');
			});
		}
	})

	.controller('Home', function (LinkService, $scope, $location) {
		$scope.showBack = false;

		$scope.submit = function() {
			$scope.error = '';
			LinkService.submit($scope.url, $scope.permalink, function(link) {
				$location.path('/view/' + link.id);
			}, function(msg) {
				$scope.error = msg;
			});
			$scope.url = $scope.permalink = '';
		};
	})

	.controller('View', function ($rootScope, $scope, $http, $routeParams, LinkService, $location) {
		$rootScope.showBack = true;

		LinkService.get($routeParams.id, function(link) {
			$scope.link = 'http://' + location.host + '/' + (link.permalink || link.id);
			$scope.hits = link.hits;

			setTimeout(function() {
				var el = document.getElementById('link');
				el.setSelectionRange(0, el.value.length)
				el.focus();
			});
		}, function() {
			console.log('fail')
		});
	});

