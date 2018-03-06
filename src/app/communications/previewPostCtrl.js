'use strict';

angular.module('eduwebApp').
controller('previewPostCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'data',
function($scope, $rootScope, $uibModalInstance, data){

	$scope.type = data.type;
	$scope.post = angular.copy(data.post);

	// for (var i = 0; i < data.post.attachment.length; i++){
		$scope.post.attachment = data.post.attachment;
	// }
	if( $scope.post.details === undefined ) $scope.post.details = data.post;

	var showName = $scope.post.details.audience;
	localStorage.setItem("theParentName", attachments);
	var exportParentName = localStorage.setItem("theParentName", showName);
	var returnParentName = localStorage.getItem("theParentName");
	console.log(returnParentName);

	var attachments = data.post.attachment;
	localStorage.setItem("attachmentsList", attachments);
	var testing12 = localStorage.setItem("attachmentsList", attachments);
	var returntestresults = localStorage.getItem("attachmentsList");
	console.log("Testing 1-2 || " + returntestresults);
	console.log(attachments);

	$scope.attachments = attachments.split(',');

	console.log($scope.attachments[0]);

	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');
	}; // end cancel



} ]);
