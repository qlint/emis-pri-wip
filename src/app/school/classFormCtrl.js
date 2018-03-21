'use strict';

angular.module('eduwebApp').
controller('classFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data){

	$scope.theClass = ( data.selectedClass !== undefined ? data.selectedClass : {} );
	$scope.deleted = false;
	$scope.clonedClass = {};
	$scope.classes = data.classes;
	
	$scope.subjectSelection = [];
	$scope.subjectExamSelection = {};
	$scope.apply_to_all_subjects = [];
	$scope.gradeWeight = {};
	$scope.reportCardTypes = ["Standard","Kindergarten","Playgroup"];
	$scope.examTypes = [];
	
	$scope.isTeacher = ( $rootScope.currentUser.user_type == 'TEACHER' ? true : false );
	$scope.edit = ( $scope.theClass.class_id !== undefined ? true : false );
	
	var getSubjects = function(classCatId)
	{
		/* if teacher of subject, only pull their subjects, else if teacher assigned to class, show all subjects */
		if( $scope.isTeacher && !$scope.canEditClass )
		{
			var params = $rootScope.currentUser.emp_id + '/' + classCatId + '/all';
			apiService.getAllTeacherSubjects(params,function(response){
				var result = angular.fromJson(response);
				if( result.response == 'success') $scope.subjects = ( result.nodata? [] : result.data );
			}, apiError);
		}
		else
		{
			var params = classCatId + '/all/0';
			apiService.getAllSubjects(params,function(response){
				var result = angular.fromJson(response);
				if( result.response == 'success') $scope.subjects = ( result.nodata? [] : result.data );
			}, apiError);
		}
	}
	
	var getClassDetails = function(classId)
	{
		var params = ( $scope.isTeacher && !$scope.canEditClass ? classId + '/0/' + $rootScope.currentUser.emp_id : classId);
		apiService.getAllClassExams(params,setClassDetails, apiError);
	}
	
	var setClassDetails = function(response,status)
	{
		var result = angular.fromJson(response);
		if( result.response == 'success')
		{
			// build the subject and exams array
			$scope.classDetails = ( result.nodata ? [] : angular.copy(result.data) );
	
			angular.forEach($scope.classDetails , function(item,key){
				if( $scope.subjectSelection.indexOf(item.subject_id) === -1 ) $scope.subjectSelection.push(item.subject_id);
			
				if( $scope.subjectExamSelection[item.subject_id] === undefined) $scope.subjectExamSelection[item.subject_id] = [];
				if( item.exam_type_id !== null ) $scope.subjectExamSelection[item.subject_id].push(item.exam_type_id);
				
				if( item.exam_type_id !== null ) 
				{
					if( $scope.gradeWeight[ item.subject_id + '-' + item.exam_type_id ] === undefined ) $scope.gradeWeight[item.subject_id + '-' + item.exam_type_id ] = {};
					if( item.grade_weight !== null ) $scope.gradeWeight[item.subject_id + '-' + item.exam_type_id ].grade_weight = item.grade_weight;
				}
				
			});
			
			getSubjects($scope.theClass.class_cat_id);
		
			apiService.getExamTypes($scope.theClass.class_cat_id, function(response){
				var result = angular.fromJson(response);
				if( result.response == 'success'){ $scope.examTypes = result.data;}
			}, apiError);
		}
	}
	
	var initializeController = function()
	{
		apiService.getAllTeachers(true,function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success') $scope.teachers = result.data;
		},apiError);

		if( $scope.edit )
		{
			getClassDetails($scope.theClass.class_id);
			
			if( $scope.isTeacher )
			{
				/* check if this teacher is assigned to this class, or only to subjects */
				/* if assigned to class, can make edits to all, subject only teachers can not edit class details */
				$scope.canEditClass = ( $scope.theClass.teacher_id == $scope.currentUser.emp_id ? true : false )
			}
			else $scope.canEditClass = true;
		
		}
		else $scope.canEditClass = true;
		
		
		
	}
	setTimeout(initializeController,1);
	
	$scope.$watch('theClass.class_cat_id',function(newVal,oldVal){
		if( newVal == oldVal ) return;

		getSubjects(newVal);
		
		apiService.getExamTypes(newVal, function(response){
			var result = angular.fromJson(response);				
			if( result.response == 'success'){ $scope.examTypes = result.data;}			
		}, apiError);
		
		
	});
		
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	$scope.cloneClass = function()
	{
		// fetch all details about selected class and populate form
		$scope.theClass = angular.copy($scope.clonedClass);
		getClassDetails($scope.theClass.class_id);
	}
	
	$scope.save = function(form)
	{

		if ( !form.$invalid ) 
		{
			var data = $scope.theClass;
			data.user_id = $rootScope.currentUser.user_id;
			
			data.subjects = [];
			
			if( $scope.edit )
			{
				if( $scope.isTeacher && !$scope.canEditClass )
				{
					// only send the teachers subject(s)
					
					angular.forEach( $scope.subjectSelection, function(subject_id,key){
						var examsArray = [];
						var class_subject_id = undefined;
						
						angular.forEach($scope.subjectExamSelection[subject_id], function(exam_type_id,key2){
									
							// get ids
							if( $scope.classDetails !== undefined ) {
								var ids = $scope.classDetails.filter(function(item){
									if( item.subject_id == subject_id && item.exam_type_id == exam_type_id ) return item;
								})[0];
							}
							class_subject_id = (ids !== undefined ? ids.class_subject_id : undefined);
							
							
							if( class_subject_id === undefined )
							{
								// check if any subjects saved without exams set
								var ids = $scope.classDetails.filter(function(item){
									if( item.subject_id == subject_id && item.exam_type_id == null ) return item;
								})[0];
								class_subject_id = (ids !== undefined ? ids.class_subject_id : undefined);
							}
						
							examsArray.push({
								exam_type_id: exam_type_id,
								class_subject_id: class_subject_id,
								class_sub_exam_id: (ids !== undefined ? ids.class_sub_exam_id : undefined),
								grade_weight: ( $scope.gradeWeight[subject_id + '-' + exam_type_id] !== undefined ? $scope.gradeWeight[subject_id + '-' + exam_type_id].grade_weight : 0)
							});
							
							
						});	
					
						
						data.subjects.push({
							subject_id: subject_id,
							class_subject_id: class_subject_id,
							exams: examsArray
						});
						
					});
					// set the teacher id to the current user
					data.teacher_id = $rootScope.currentUser.emp_id;
					apiService.updateTeacherSubject(data,createCompleted,apiError);
				}
				else
				{
					angular.forEach( $scope.subjectSelection, function(subject_id,key){
						var examsArray = [];
						var class_subject_id = undefined;
						
						angular.forEach($scope.subjectExamSelection[subject_id], function(exam_type_id,key2){
									
							// get ids
							if( $scope.classDetails !== undefined ) {
								var ids = $scope.classDetails.filter(function(item){
									if( item.subject_id == subject_id && item.exam_type_id == exam_type_id ) return item;
								})[0];
							}
							class_subject_id = (ids !== undefined ? ids.class_subject_id : undefined);
							
							
							if( class_subject_id === undefined )
							{
								// check if any subjects saved without exams set
								var ids = $scope.classDetails.filter(function(item){
									if( item.subject_id == subject_id && item.exam_type_id == null ) return item;
								})[0];
								class_subject_id = (ids !== undefined ? ids.class_subject_id : undefined);
							}
						
							examsArray.push({
								exam_type_id: exam_type_id,
								class_subject_id: class_subject_id,
								class_sub_exam_id: (ids !== undefined ? ids.class_sub_exam_id : undefined),
								grade_weight: ( $scope.gradeWeight[subject_id + '-' + exam_type_id] !== undefined ? $scope.gradeWeight[subject_id + '-' + exam_type_id].grade_weight : 0)
							});
							
							
						});	
					
						
						data.subjects.push({
							subject_id: subject_id,
							class_subject_id: class_subject_id,
							exams: examsArray
						});
						
					});
					apiService.updateClass(data,createCompleted,apiError);	
				}
			}
			else
			{
				
				angular.forEach( $scope.subjectSelection, function(subject_id,key){
					
					var examsArray = [];
					angular.forEach($scope.subjectExamSelection[subject_id], function(exam_type_id,key2){
						examsArray.push({
							exam_type_id: exam_type_id,
							grade_weight: ( $scope.gradeWeight[subject_id + '-' + exam_type_id] !== undefined ? $scope.gradeWeight[subject_id + '-' + exam_type_id].grade_weight : 0)
						});
					});
					
					data.subjects.push({
						subject_id: subject_id,
						exams: examsArray
					});
					
				});
				apiService.addClass(data,createCompleted,apiError);
			}
			
			
		}
	}
	
	var createCompleted = function ( response, status, params ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			var msg = ($scope.deleted ? 'Class was deleted.' : ( $scope.edit  ? 'Class was updated' :  'Class was added.'));
			$rootScope.$emit('classAdded', {'msg' : msg, 'clear' : true});
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
		var msg = ( result.data.indexOf('"U_active_class_name"') > -1 ? 'This class already exists.' : result.data);
		$scope.errMsg = msg;
	}
	
	$scope.deleteClass = function()
	{
	$scope.error = false;
		apiService.checkClass($scope.theClass.class_id,function(response,status){
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				var canDelete = ( parseInt(result.data.num_exams) == 0 ? true : false );
				
				if( canDelete )
				{
					var dlg = $dialogs.confirm('Delete Class','Are you sure you want to permanently delete class <strong>' + $scope.theClass.class_name + '</strong>? ',{size:'sm'});
					dlg.result.then(function(btn){
						$scope.deleted = true;
						apiService.deleteClass($scope.theClass.class_id,createCompleted,apiError);
					});
				}
				else
				{
					var dlg = $dialogs.confirm('Please Confirm','Class <strong>' + $scope.theClass.class_name + '</strong> is associated with <b>' + result.data.num_exams + '</b> classes. Are you sure you want to mark this class as in-active? ',{size:'sm'});
					dlg.result.then(function(btn){
						var data = {
							user_id : $rootScope.currentUser.user_id,
							class_id: $scope.theClass.class_id,
							status: 'f'
						}
						apiService.setClassStatus(data,createCompleted,apiError);

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
	
	$scope.activateClass = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to re-activate this class? ',{size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id : $rootScope.currentUser.user_id,
				class_id: $scope.theClass.class_id,
				status: 't'
			}
			apiService.setClassStatus(data,createCompleted,apiError);

		});
		
	}
	
	$scope.addClassCat = function()
	{		
		// show small dialog with add form
		var dlg = $dialogs.create('addClassCategory.html','addClassCategoryCtrl',{},{size: 'sm',backdrop:'static'});
		dlg.result.then(function(category){
			if( $rootScope.classCats === undefined ) $rootScope.classCats = [];
			$rootScope.classCats.push(category);
					
		},function(){
			
		});
	}
	
	$scope.addSubject = function()
	{		
		// show small dialog with add form
		var domain = window.location.host;
		var dlg = $dialogs.create('http://' + domain + '/app/school/subjectForm.html','subjectFormCtrl',{class_cat_id:$scope.theClass.class_cat_id},{size: 'md',backdrop:'static'});
		dlg.result.then(function(subject){
			
			getSubjects($scope.theClass.class_cat_id);
					
		},function(){
			
		});
	}
	
	$scope.addExamType = function()
	{		
		// show small dialog with add form
		
				
		var data = {
			class_cat_id:$scope.theClass.class_cat_id
		}
		var dlg = $dialogs.create('addExamType.html','addExamTypeCtrl',data,{size: 'sm',backdrop:'static'});
		dlg.result.then(function(examType){
			
			if( $scope.examTypes === undefined ) $scope.examTypes = [];
			$scope.examTypes.push(examType);
					
		},function(){
			
		});
	}
	
	$scope.toggleSubjects = function(subject_id)
	{
		var id = $scope.subjectSelection.indexOf(subject_id);

		// is currently selected
		if (id > -1) {
			$scope.subjectSelection.splice(id, 1);
			$scope.subjectExamSelection[subject_id] = undefined;
		}

		// is newly selected
		else {
			if( $scope.subjectSelection.indexOf(subject_id) === -1 )
			{
				$scope.subjectSelection.push(subject_id);
				$scope.subjectExamSelection[subject_id] = [];
			}
		}
	}
	
	$scope.toggleSubjectExam = function(subject_id,exam_type_id)
	{
		if( $scope.subjectExamSelection[subject_id] === undefined ) $scope.subjectExamSelection[subject_id] = []; 
		var id = $scope.subjectExamSelection[subject_id].indexOf(exam_type_id);

		// is currently selected
		if (id > -1) {
			$scope.subjectExamSelection[subject_id].splice(id, 1);
		}

		// is newly selected
		else {
			$scope.subjectExamSelection[subject_id].push(exam_type_id);
		}
		
	}
	
	$scope.$watch('subject.apply_to_all', function(newVal,oldVal){
		if( newVal == oldVal ) return;
		
		if( newVal )
		{
			angular.forEach($scope.subjects, function(item,key){
				$scope.subjectSelection.push(item.subject_id);
				$scope.subjectExamSelection[item.subject_id] = [];
			});
		}
		else
		{
			angular.forEach($scope.subjects, function(item,key){
				$scope.subjectSelection = [];
				$scope.subjectExamSelection[item.subject_id] = undefined;
			});
		}
		
	});
	
	$scope.toggleAllExams = function(exam_type_id,index)
	{
		var off = ( $scope.apply_to_all_subjects[index] === true ? true : false );
		if( off )
		{
			angular.forEach($scope.subjects, function(item,key){
				if( $scope.subjectExamSelection[item.subject_id] === undefined ) $scope.subjectExamSelection[item.subject_id] = [];
				$scope.subjectExamSelection[item.subject_id].push(exam_type_id);
			});
		}
		else
		{
			angular.forEach($scope.subjects, function(item,key){
				var id = $scope.subjectExamSelection[item.subject_id].indexOf(exam_type_id);
				$scope.subjectExamSelection[item.subject_id].splice(id, 1);
			});
		}
		
	};
	
	
} ])
.controller('addExamTypeCtrl', ['$scope','$rootScope','$uibModalInstance','apiService','data',
function($scope,$rootScope,$uibModalInstance,apiService,data){		
		
		$scope.examType = {};
		$scope.examType.class_cat_id = data.class_cat_id;
		
		if ( $rootScope.currentUser.user_type == 'TEACHER' )
		{
			apiService.getClassCats($rootScope.currentUser.emp_id, function(response){
				var result = angular.fromJson(response);
				
				if( result.response == 'success') $scope.classCats = result.data;
				
			}, apiError);
		}
		else $scope.classCats = $rootScope.classCats;
		
		$scope.cancel = function(){
			$uibModalInstance.dismiss('Canceled');
		}; // end cancel
		
		$scope.save = function()
		{
			$scope.examType.user_id = $rootScope.currentUser.user_id;
			apiService.addExamType($scope.examType, createCompleted, apiError);
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
			var msg = ( result.data.indexOf('"U_exam_type_per_category"') > -1 ? 'This exam type has already been entered for this class category.' : result.data);
			$scope.errMsg = msg;
		}
		
		$scope.hitEnter = function(evt){
			if( angular.equals(evt.keyCode,13) )
				$scope.save();
		};
		

	
	
	}]) // end controller
.run(['$templateCache',function($templateCache){
  		$templateCache.put('addExamType.html',
			'<div class="modal-header dialog-header-form">'+
				'<h4 class="modal-title"><span class="glyphicon glyphicon-plus"></span> Add Exam Type</h4>' +
			'</div>' +
			'<div class="modal-body cleafix">' +
				'<ng-form name="catDialog" class="form-horizontal modalForm" novalidate role="form">' +
					'<div ng-show="error" class="alert alert-danger">' +
						'{{errMsg}}'+
					'</div>' +
					'<!-- exam type -->' +
					'<div class="form-group" ng-class="{ \'has-error\' : catDialog.exam_type.$invalid && (catDialog.exam_type.$touched || catDialog.$submitted) }">' +
						'<label for="exam_type" class="col-sm-3 control-label">Exam Type</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="name" ng-model="examType.exam_type" class="form-control"  >' +
							'<p ng-show="catDialog.exam_type.$invalid && (catDialog.exam_type.$touched || catDialog.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Exam Type is required.</p>' +
						'</div>' +
					'</div>' +
					'<!-- class category -->' +
					'<div class="form-group ng-class="{ \'has-error\' : catDialog.class_cat.$invalid && (catDialog.class_cat.$touched || catDialog.$submitted) }">' +
						'<label for="class_cat" class="col-sm-3 control-label">Class Category</label>' +
						'<div class="col-sm-9">' +
							'<select name="class_cat" class="form-control" ng-options="cat.class_cat_id as cat.class_cat_name for cat in classCats"  ng-model="examType.class_cat_id" required>' +
								'<option value="">--select class category--</option>' +
							'</select>' +
							'<p ng-show="catDialog.class_cat.$invalid && (catDialog.class_cat.$touched || catDialog.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Class category is required.</p>' +
						'</div>' +
					'</div>' +
				'</ng-form>' +
			'</div>'+
			'<div class="modal-footer">' +
				'<button type="button" class="btn btn-link" ng-click="cancel()">Cancel</button>' +
				'<button type="button" class="btn btn-primary" ng-click="save()">Save</button>' +
			'</div>'
		);
}]);