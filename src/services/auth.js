'use strict';

angular.module('eduwebApp')
.factory('Auth', [ '$http', '$rootScope', '$window', 'Session', 'AUTH_EVENTS', 'ajaxService',
function($http, $rootScope, $window, Session, AUTH_EVENTS, ajaxService) {
	var authService = {};

	//the login function
	authService.login = function(user, success, error) {

		/***** REMOVE ME : TEST ****
		var loginData = {"response":"success","data":user};
		$window.sessionStorage["userInfo"] = JSON.stringify(loginData.data);
		Session.create(loginData.data);
		$rootScope.currentUser = loginData.data;
		$rootScope.$broadcast(AUTH_EVENTS.loginSuccess);
		success(loginData.data);
		***************************/

		var domain = window.location.host;
		// var path = ( domain.indexOf('dev.eduweb.co.ke') > -1 ? 'http://devapi.eduweb.co.ke' : (domain.indexOf('eduweb.co.ke') > -1  ? 'http://api.eduweb.co.ke': 'http://api.eduweb.localhost'));
		var path = 'http://localhost:8010/api_primary';


		var subdomain = domain.substr(0, domain.indexOf('.'));
		var apiAction = ( subdomain == 'parents' ? 'parentLogin' : 'login');


	    ajaxService.AjaxPost(user, path + "/" + apiAction,
			function(loginData){

				if( loginData.response == 'success' )
				{
					// success
					if( apiAction == 'login' )
					{
						loginData.data.settings = loginData.data.settings.reduce(function ( total, current ) {
							total[ current.name ] = current.value;
							return total;
						}, {});
					}
					else
					{
						loginData.data.user_type = 'PARENT';
					}

					//set the browser session, to avoid re-login on refresh
					$window.sessionStorage["userInfo"] = JSON.stringify(loginData.data);

					//update current user into the Session service or $rootScope.currentUser
					//whatever you prefer
					Session.create(loginData.data);

					$rootScope.currentUser = loginData.data;

					//fire event of successful login
					$rootScope.$broadcast(AUTH_EVENTS.loginSuccess);
					//run success function
					success(loginData.data);

				}
				else
				{
					//console.log('login failed');
					console.log(loginData);
					$rootScope.$broadcast(AUTH_EVENTS.loginFailed, {errorMsg: loginData.data});
				}
			},
			function(e){
				// error
				//unsuccessful login, fire login failed event for
				//the according functions to run
				//console.log('login failed');
				$rootScope.$broadcast(AUTH_EVENTS.loginFailed, {errorMsg: e.data});
				error();
			});


	};

	//check if the user is authenticated
	authService.isAuthenticated = function() {
		//console.log(Session.user);
		return !!Session.user;
	};

	//check if the user is authorized to access the next route
	//this function can be also used on element level
	//e.g. <p ng-if="isAuthorized(authorizedRoles)">show this only to admins</p>
	authService.isAuthorized = function(authorizedRoles) {
		if (!angular.isArray(authorizedRoles)) {
	      authorizedRoles = [authorizedRoles];
	    }

	    return (authService.isAuthenticated() &&
	      authorizedRoles.indexOf(Session.userRole) !== -1);
	};

	//log out the user and broadcast the logoutSuccess event
	authService.logout = function(){
		Session.destroy();
		$window.sessionStorage.removeItem("userInfo");
		console.log('here');
		$rootScope.$broadcast(AUTH_EVENTS.logoutSuccess);
	};

	return authService;
} ]);
