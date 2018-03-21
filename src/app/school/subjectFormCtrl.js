'use strict';

angular.module('eduwebApp').
controller('subjectFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data){
	
	$scope.edit = ( data.subject && data.subject.subject_id !== undefined ? true : false );
	$scope.deleted = false;
	
	
	$scope.initializeController = function()
	{
		apiService.getAllTeachers(true,function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success') $scope.teachers = result.data;
		},apiError);
		
		if( $scope.edit )
		{
			$scope.subject = ( data.subject !== undefined ? data.subject : {} );	
		}
		else{
			$scope.subject = {};
			$scope.subject.class_cat_id = ( data.class_cat_id !== undefined ? data.class_cat_id : {} );
		}
		
		// get subjects
		if( $scope.subject.class_cat_id !== undefined )
		{
			var params = $scope.subject.class_cat_id + '/true/0';
			apiService.getAllSubjects(params, function(response,status,params){
				var result = angular.fromJson(response);
				if( result.response == 'success') $scope.subjects = ( result.nodata ? [] : result.data );
			}, apiError);
		}
		
	}
	$scope.initializeController();
	
	$scope.$watch('subject.class_cat_id', function(newVal, oldVal){
		if( newVal == oldVal) return;
		
		// get subjects
		var params = newVal + '/true/0';
		apiService.getAllSubjects(params, function(response,status,params){
			var result = angular.fromJson(response);
			if( result.response == 'success') $scope.subjects = ( result.nodata ? [] : result.data );
		}, apiError);
	});
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	$scope.save = function(form)
	{
		
		if ( !form.$invalid ) 
		{
			var data = $scope.subject;
			data.user_id = $rootScope.currentUser.user_id;
			data.use_for_grading = (data.use_for_grading ? 't' : 'f');
			
			if( $scope.edit )
			{
				apiService.updateSubject(data,createCompleted,apiError);
			}
			else
			{
				apiService.addSubject(data,createCompleted,apiError);
			}
			
			
		}
	}
	var createCompleted = function ( response, status, params ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			var msg = ($scope.deleted ? 'Subject was deleted.' : ( $scope.edit ? 'Subject was updated' :  'Subject was added.'));
			$rootScope.$emit('subjectAdded', {'msg' : msg, 'clear' : true});
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}
	
	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		var msg = ( result.data.indexOf('"U_subject_by_class_cat"') > -1 ? 'This subject already exists.' : result.data);
		$scope.errMsg = msg;
	}
	
	$scope.deleteSubject = function()
	{
		$scope.error = false;
		apiService.checkSubject($scope.subject.subject_id,function(response,status){
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				var canDelete = ( parseInt(result.data.num_classes) == 0 ? true : false );
				
				if( canDelete )
				{
					var dlg = $dialogs.confirm('Delete Subject','Are you sure you want to permanently delete subject <strong>' + $scope.subject.subject_name + '</strong>? ',{size:'sm'});
					dlg.result.then(function(btn){
						$scope.deleted = true;
						apiService.deleteSubject($scope.subject.subject_id,createCompleted,apiError);
					});
				}
				else
				{
					var dlg = $dialogs.confirm('Please Confirm','Subject <strong>' + $scope.subject.subject_name + '</strong> is associated with <b>' + result.data.num_classes + '</b> classes. Are you sure you want to mark this subject as in-active? ',{size:'sm'});
					dlg.result.then(function(btn){
						var data = {
							user_id : $rootScope.currentUser.user_id,
							subject_id: $scope.subject.subject_id,
							status: 'f'
						}
						apiService.setSubjectStatus(data,createCompleted,apiError);

					});
				}
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
		},apiError)
		
		
	}
	
	$scope.activateSubject = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to re-activate this subject? ',{size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id : $rootScope.currentUser.user_id,
				subject_id: $scope.subject.subject_id,
				status: 't'
			}
			apiService.setSubjectStatus(data,createCompleted,apiError);

		});
		
	}
	
	$scope.addClassCat = function()
	{		
		// show small dialog with add form
		var dlg = $dialogs.create('addClassCategory.html','addClassCategoryCtrl',{},{size: 'sm',backdrop:'static'});
		dlg.result.then(function(category){
			
			$rootScope.classCats.push(category);
					
		},function(){
			
		});
	}
	
	
	
} ])
.controller('addClassCategoryCtrl',[ '$scope','$rootScope','$uibModalInstance','apiService','dialogs','data',
function($scope,$rootScope,$uibModalInstance,apiService,$dialogs,data){
		
		$scope.edit = (data.item !== undefined ? true : false);
		$scope.classCat = data.item || {};
		
		$scope.cancel = function(){
			$uibModalInstance.dismiss('Canceled');
		}; // end cancel
		
		$scope.save = function()
		{
			if( $scope.edit )
			{
				var dlg = $dialogs.confirm('Update Class Category','Are you sure you want to update this class category? It will also update all students that are associated with this category.', {size:'sm'});
				dlg.result.then(function(btn){
					var data = {
						class_cat_id : $scope.classCat.class_cat_id,
						class_cat_name : $scope.classCat.class_cat_name,
						user_id: $rootScope.currentUser.user_id
					}
					apiService.updateClassCat(data, createCompleted, apiError);
				});
			}
			else
			{
				var data = {
					class_cat_name : $scope.classCat.class_cat_name,
					user_id: $rootScope.currentUser.user_id
				}
				apiService.addClassCat(data, createCompleted, apiError);
			}
		}; // end save
		
		
		var createCompleted = function(response,status)
		{
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				$uibModalInstance.close(result.data);
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
		}
		
		
		var apiError = function(response,status)
		{
			var result = angular.fromJson( response );
			$scope.error = true;
			var msg = ( result.data.indexOf('"U_active_class_cat"') > -1 ? 'The Class Category name you entered already exists.' : result.data);
			$scope.errMsg = msg;
		}
		
		$scope.hitEnter = function(evt){
			if( angular.equals(evt.keyCode,13) )
				$scope.save();
		};
		

	
	
	}]) // end controller(addCargoCtrl)
.run(['$templateCache',function($templateCache){
  		$templateCache.put('addClassCategory.html',
			'<div class="modal-header dialog-header-form">'+
				'<h4 class="modal-title"><span class="glyphicon glyphicon-plus"></span> {{ (edit ? \'Update\' : \'Add\') }} Class Category</h4>' +
			'</div>' +
			'<div class="modal-body cleafix">' +
				'<ng-form name="catDialog" class="form-horizontal modalForm" novalidate role="form">' +
					'<div ng-show="error" class="alert alert-danger">' +
						'{{errMsg}}'+
					'</div>' +
					'<!-- class_cat_name -->' +
					'<div class="form-group" ng-class="{ \'has-error\' : catDialog.class_cat_name.$invalid && (catDialog.class_cat_name.$touched || catDialog.$submitted) }">' +
						'<label for="class_cat_name" class="col-sm-3 control-label">Class Category Name</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="class_cat_name" ng-model="classCat.class_cat_name" class="form-control" required >' +
							'<p ng-show="catDialog.class_cat_name.$invalid && (catDialog.class_cat_name.$touched || catDialog.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Class Category Name is required.</p>' +
						'</div>' +
					'</div>' +
				'</ng-form>' +
			'</div>'+
			'<div class="modal-footer">' +
				'<button type="button" class="btn btn-link" ng-click="cancel()">Cancel</button>' +
				'<button type="button" class="btn btn-primary" ng-click="save()">{{ (edit ? \'Update\' : \'Save\') }}</button>' +
			'</div>'
		);
}]);