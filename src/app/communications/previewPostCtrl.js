'use strict';

angular.module('eduwebApp').
controller('previewPostCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'data',
function($scope, $rootScope, $uibModalInstance, data){

	$scope.type = data.type;
	$scope.post = angular.copy(data.post);
	if( $scope.post.details === undefined ) $scope.post.details = data.post;
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	
	
} ]);