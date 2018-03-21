'use strict';

angular.module('eduwebApp').
controller('addEmployeeCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'FileUploader', 'data','$parse',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, FileUploader, data, $parse){
	
	$scope.employee = {};
	var start_date = moment().format('YYYY-MM-DD HH:MM');
	$scope.employee.joined_date = {startDate:start_date};
	$scope.employee.country = 'Kenya';
	$scope.employee.status = 'true';

	
	$scope.initializeController = function()
	{
		$scope.departments = $rootScope.allDepts;
		
	}
	$scope.initializeController();
	
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	$scope.$watch('employee.emp_cat', function(newVal, oldVal){
		if( newVal == oldVal) return;
		
		if( newVal === undefined || newVal === null || newVal == '' ) 	$scope.departments = $rootScope.allDepts;
		else
		{	
			// filter dept to only show those belonging to the selected category
			$scope.departments = $rootScope.allDepts.reduce(function(sum,item){
				if( item.category == newVal.emp_cat_name ) sum.push(item);
				return sum;
			}, []);
			$scope.employee.emp_cat_id = newVal.emp_cat_id;
			
			if( newVal.emp_cat_name == 'Teaching' ) $scope.employee.user_type = 'TEACHER';
		}
		
	});
	
	$scope.addEmpCat = function()
	{
		var dlg = $dialogs.create('addEmpCategory.html','addEmpCategoryCtrl',{},{size: 'sm',backdrop:'static'});
		dlg.result.then(function(category){
			$rootScope.empCats = undefined;
			$rootScope.getEmpCats();		
		},function(){
			
		});
	}
	
	$scope.addDept = function()
	{		
		// show small dialog with add form
		var domain = window.location.host;
		var dlg = $dialogs.create('http://' + domain + '/app/school/departmentForm.html','departmentFormCtrl',{emp_cat_name: $scope.employee.emp_cat.emp_cat_name},{size: 'md',backdrop:'static'});
		dlg.result.then(function(){
			
			// update departments
			apiService.getDepts(true,function(response){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					if( result.nodata )
					{
						$scope.departments = [];
					}
					else
					{
						// filter dept to only show those belonging to the selected category
						$rootScope.allDepts = result.data;
						$scope.departments = $rootScope.allDepts.reduce(function(sum,item){
							if( item.category == $scope.employee.emp_cat.emp_cat_name ) sum.push(item);
							return sum;
						}, []);
					}
					
				}
			}, function(){});

					
		},function(){
			
		});
	}
	
	
	$scope.save = function(theForm)
	{	
		if( !theForm.$invalid )
		{
			if( uploader.queue[0] !== undefined )
			{
				// need a unique filename
				$scope.filename = moment() + '_' + uploader.queue[0].file.name;
				uploader.queue[0].file.name = $scope.filename;
				uploader.uploadAll();
				$scope.employee.emp_image = $scope.filename;
			}
			
			var postData = angular.copy($scope.employee);
			postData.joined_date = moment($scope.employee.joined_date.startDate).format('YYYY-MM-DD');
			postData.user_id = $rootScope.currentUser.user_id;
			postData.active = ( $scope.employee.status == 'true' ? 't' : 'f' );
			
			apiService.addEmployee(postData, createCompleted, createError);
		}
		else
		{
			$scope.formError = true;
			$scope.errMsg = "There were errors found in the form.";
		}
	}
	
	var createCompleted = function ( response, status ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			if( uploader.queue[0] !== undefined )
			{
				$scope.employee.emp_image = $scope.filename;
			}
			$uibModalInstance.close();
			$rootScope.$emit('employeeAdded', {'msg' : 'Employee was created.', 'clear' : true});
		}
		else
		{
			$scope.formError = true;
			$scope.errMsg = result.data;
		}
	}
	
	var createError = function () 
	{
		var result = angular.fromJson( response );
		$scope.formError = true;
		$scope.errMsg = result.data;
	}
	
	var uploader = $scope.uploader = new FileUploader({
            url: 'upload.php',
			formData : [{
				'dir': 'employees'
			}]
    });
	
} ]);