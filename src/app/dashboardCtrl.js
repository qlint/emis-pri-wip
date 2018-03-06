'use strict';

angular.module('eduwebApp').
controller('dashboardCtrl', ['$scope', '$rootScope', 'apiService',
function($scope, $rootScope, apiService){

	$scope.studentsLoading = true;
	$scope.staffLoading = true;
	$scope.examsLoading = true;
	$scope.fees1Loading = true;
	$scope.fees2Loading = true;
	$scope.fees3Loading = true;
	$scope.newsLoading = true;

	$scope.isTeacher = ( $rootScope.currentUser.user_type == 'TEACHER' ? true : false);

	var getStudentCount = function()
	{

		apiService.getClassCatsSummary(status, function(response){

			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.classCats = result.data;
				$scope.studentsLoading = false;
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
				$scope.studentsLoading = false;
			}

		}, function(){});
	}

	var getTeacherClasses = function()
	{
		var params = $rootScope.currentUser.emp_id + '/true';
		apiService.getTeacherClasses(params, function(response){

			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.myClasses =  ( result.nodata !== undefined ? [] : result.data);
				$scope.studentsLoading = false;
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
				$scope.studentsLoading = false;
			}

		}, function(){});
	}

	var getTeacherSubjects = function()
	{
		var params = $rootScope.currentUser.emp_id + '/true';
		apiService.getTeacherSubjects(params, function(response){

			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.subjects = ( result.nodata !== undefined ? [] : result.data);
				$scope.studentsLoading = false;
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
				$scope.studentsLoading = false;
			}

		}, function(){});
	}


	var getStaffCount = function()
	{
		apiService.getDeptSummary(status, function(response){

			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.deptCats = result.data;
				$scope.staffLoading = false;
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
				$scope.staffLoading = false;
			}

		}, function(){});
	}

	var getFeeSummary = function()
	{
		// get current term
		apiService.getCurrentTerm({},function(response){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.currentTerm = result.data || undefined;
				if( $scope.currentTerm !== undefined )
				{
					$scope.currentTermTitle = $scope.currentTerm.term_name + ' ' + $scope.currentTerm.year;
					var end_date = moment().add(1,'day').format('YYYY-MM-DD');
					$scope.date = {startDate: $scope.currentTerm.start_date, endDate: end_date};
					getPaymentsReceived($scope.currentTerm.start_date, end_date);
				}
				else
				{
					$scope.fees1Loading = false;
					$scope.numPaymentsReceived = 0;
				}
			}
		},function(){});

		// get payments due this month
		var start_date = moment().startOf('month').format('YYYY-MM-DD');
		var end_date = moment().endOf('month').format('YYYY-MM-DD');
		getPaymentsDue(start_date, end_date);

		getOverDuePayments();
	}

	var getPaymentsReceived = function(startDate, endDate)
	{
		// get payments received for current term, that has not been reversed
		var request = startDate + "/" + endDate + "/false";
		apiService.getPaymentsReceived(request, loadPaymentsReceived, apiError);
	}

	var loadPaymentsReceived = function(response, status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.numPaymentsReceived = ( result.nodata !== undefined ? 0 : result.data.length);
			$scope.fees1Loading = false;
		}
		else
		{
			$scope.prError = true;
			$scope.prErrMsg = result.data;
			$scope.fees1Loading = false;
		}
	}

	var getPaymentsDue = function(startDate, endDate)
	{
		// get payments received for curren term
		var request = startDate + "/" + endDate;
		apiService.getPaymentsDue(request, loadPaymentsDue, apiError);
	}

	var loadPaymentsDue = function(response, status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.numPaymentsDue = ( result.nodata !== undefined ? 0 : result.data.length);
			$scope.fees2Loading = false;
		}
		else
		{
			$scope.prError = true;
			$scope.prErrMsg = result.data;
			$scope.fees2Loading = false;
		}
	}

	var getOverDuePayments = function()
	{
		apiService.getPaymentsPastDue({}, loadPaymentsPastDue, apiError);
	}

	var loadPaymentsPastDue = function(response, status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.numPaymentsPastDue = ( result.nodata !== undefined ? 0 : result.data.length);
			$scope.fees3Loading = false;
		}
		else
		{
			$scope.prError = true;
			$scope.prErrMsg = result.data;
			$scope.fees3Loading = false;
		}
	}

	var getTopStudents = function()
	{
		if( $scope.isTeacher ) apiService.getTeacherTopStudents($rootScope.currentUser.emp_id, loadTopStudents, apiError);
		else apiService.getTopStudents(undefined, loadTopStudents, apiError);
	}

	var loadTopStudents = function(response, status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.examsLoading = false;
			var topStudents = ( result.nodata !== undefined ? [] : result.data);

			// group into classes object

			$scope.classes = [];
			var lastClass = '';
			var students = [];
			var i = 0;
			angular.forEach(topStudents, function(item,key){

				if( item.class_id != lastClass )
				{
					// changing to new subject, store the marks
					if( i > 0 ) $scope.classes[(i-1)].students = students;

					$scope.classes.push(
						{
							class_id: item.class_id,
							class_name: item.class_name,
							students: []
						}
					);

					students = [];
					i++;
				}
				students.push({
					student_name: item.student_name,
					student_id: item.student_id,
					total_mark: item.total_mark,
					total_grade_weight: item.total_grade_weight,
					position: item.rank,
					position_out_of: item.position_out_of,
					grade: item.grade
				});

				lastClass = item.class_id;

			});
			if( $scope.classes[(i-1)] ) $scope.classes[(i-1)].students = students;

		}
		else
		{
			$scope.prError = true;
			$scope.prErrMsg = result.data;
			$scope.examsLoading = false;
		}
	}

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}

	var initializeController = function ()
	{
		if( $scope.isTeacher )
		{
			getTeacherClasses();
			getTeacherSubjects();
			getTopStudents();
		}
		else
		{
			getStudentCount();
			getStaffCount();
			getFeeSummary();
			getTopStudents();
		}
	}

	setTimeout(initializeController(),10);


	$scope.$on('$destroy', function() {
		if($scope.dataGrid){
			$('.fixedHeader-floating').remove();
			$scope.dataGrid.fixedHeader.destroy();
			$scope.dataGrid.clear();
			$scope.dataGrid.destroy();
		}
		$rootScope.isModal = false;
    });


} ]);
