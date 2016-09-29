'use strict';

angular.module('eduwebApp').
controller('reportCardCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data','$timeout','$window','$parse',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $timeout, $window, $parse){

	$rootScope.isPrinting = false;
	$scope.student = data.student || undefined;
	$scope.reportCardType = ($scope.student !== undefined ? $scope.student.report_card_type : undefined);
	$scope.showSelect = ( $scope.student === undefined ? true : false );
	$scope.classes = data.classes || [];
	$scope.terms = data.terms || [];
	$scope.filters = data.filters || [];
	$scope.adding = data.adding;
	$scope.thestudent = {};
	$scope.examTypes = {};
	$scope.comments = {};
	
	$scope.canPrint = false;
	
	$scope.report = {};
	
	$scope.showReportCard = false;
	
	$scope.isTeacher = ( $rootScope.currentUser.user_type == 'TEACHER' ? true : false );
	$scope.isAdmin = ( $rootScope.currentUser.user_type == 'SYS_ADMIN' ? true : false );
	
	var initializeController = function()
	{
		if( $scope.reportCardType != 'Kindergarten' )
		{
			// get exam types
			apiService.getExamTypes($scope.filters.class.class_cat_id, function(response){
				var result = angular.fromJson(response);				
				if( result.response == 'success')
				{ 
					$scope.rawExamTypes = result.data;
					if( $scope.reportCardType === undefined ) $scope.reportCardType = $scope.filters.class.report_card_type; // set the report card type if not passed in
					
					initReportCard();
				}
				
			}, apiError);
		}
		else
		{
			initReportCard();
		}
			
		// if no student was passed in, get list of students for select dropdown
		if( $scope.student === undefined )
		{
			if ( $rootScope.currentUser.user_type == 'TEACHER' )
			{
				var params = $rootScope.currentUser.emp_id + '/' + true;
				apiService.getTeacherStudents(params, loadStudents, apiError);
			}
			else
			{
				apiService.getAllStudents(true, loadStudents, apiError);
			}
		}
	
		
	}
	$timeout(initializeController,1);
	
	var initReportCard = function()
	{

		if( data.reportData !== undefined )
		{
			/* passing in a report to view, load it */			
			$scope.savedReportData = angular.fromJson(data.reportData);
			$scope.originalData = angular.copy($scope.savedReportData);
			
			$scope.report.report_card_id = data.report_card_id;
			$scope.report.class_name = data.class_name;
			$scope.report.class_id = data.class_id;
			$scope.report.term = data.term_name;
			$scope.report.term_id = data.term_id;
			$scope.report.year = data.year;
			$scope.reportCardType = data.report_card_type;
			$scope.report.teacher_id = data.teacher_id;
			$scope.report.teacher_name = data.teacher_name;
			$scope.comments.teacher_name = data.teacher_name;
			//$scope.report.head_teacher_name = data.head_teacher_name;
			$scope.report.date = data.date;
			$scope.nextTermStartDate = $scope.savedReportData.nextTerm;
			$scope.currentTermEndDate = $scope.savedReportData.closingDate;
			
			$scope.savedReport = true;
			$scope.canPrint = true;
			$scope.canDelete = ( $scope.isTeacher ? false : true);
			$scope.filters = data.filters;
			$scope.isClassTeacher = ( $scope.student.class_teacher_id == $rootScope.currentUser.emp_id ? true : false);
			
			// fetch the report cards subjects based on user type
			getExamMarksforReportCard();

		}
	}
	
	var loadStudents = function(response)
	{
		var result = angular.fromJson(response);
		
		if( result.response == 'success')
		{
			$scope.students = ( result.nodata ? {} : $rootScope.formatStudentData(result.data) );				
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
		
	}

	$scope.$watch('thestudent.selected', function(newVal,oldVal){
		if( newVal == oldVal ) return;
		$scope.showReportCard = false;
		$scope.student = $scope.thestudent.selected;
		$scope.reportCardType = $scope.student.report_card_type;
		
		$scope.isClassTeacher = ( $scope.student.class_teacher_id == $rootScope.currentUser.emp_id ? true : false);

	});
	
	$scope.$watch('filters.class', function(newVal,oldVal){
		if( newVal == oldVal ) return;
		
		$scope.reportCardType = newVal.report_card_type;
		
		if( $scope.reportCardType != 'Kindergarten' )
		{
			// get exam types
			apiService.getExamTypes(newVal.class_cat_id, function(response){
				var result = angular.fromJson(response);				
				if( result.response == 'success')
				{ 
					$scope.rawExamTypes = result.data;					
				}
				
			}, apiError);
		}
	});
	
	$scope.$watch('filters.term', function(newVal,oldVal){
		if( newVal == oldVal ) return;

		$scope.currentTermEndDate = newVal.end_date;

		/* get the next term based on the selected term for report card */
		apiService.getNextTerm(newVal.end_date, function(response,status){
			var result = angular.fromJson(response);				
			if( result.response == 'success')
			{ 
				$scope.nextTermStartDate = ( result.nodata !== undefined ? '' : result.data.start_date);
			}
		}, apiError);
	});
	
	$scope.clearSelect = function($event) 
	{
		$event.stopPropagation(); 
		$scope.thestudent.selected = undefined;
		$scope.showReportCard = false;
		$scope.report = {};
		$scope.overall = {};
		$scope.overallLastTerm = {};
		//$scope.examTypes = {};
		$scope.reportData = undefined;
	};
	
	
	$scope.getProgressReport = function(recreate)
	{
		$scope.showReportCard = false;
		$scope.report = {};
		$scope.overall = {};
		$scope.overallLastTerm = {};
		$scope.reportData = undefined;
		$scope.comments = {};
		$scope.recreated = false;

		
		$scope.currentFilters = angular.copy($scope.filters);
		console.log($scope.currentFilters);
		$scope.report.class_name = $scope.currentFilters.class.class_name;
		$scope.report.class_id = $scope.currentFilters.class.class_id;
		$scope.report.term = $scope.currentFilters.term.term_name;
		$scope.report.term_id = $scope.currentFilters.term.term_id;
		$scope.report.year = $scope.currentFilters.term.year;
		$scope.report.teacher_id = $scope.currentFilters.class.teacher_id;
		$scope.report.teacher_name = $scope.currentFilters.class.teacher_name;
		$scope.comments.teacher_name = $scope.currentFilters.class.teacher_name;
		
		// check to see if there is already a report card with this criteria
		if( recreate )
		{
			/* user has requested to recreate an existing report card, fetch student exam marks and build report */
			$scope.savedReport = false;
			$scope.recreated = true;
			
			var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
			if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
			apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);
		}
		else
		{
			getExamMarksforReportCard();
			//var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id
			//apiService.getStudentReportCard(params,loadReportCard, apiError);
		}
		
	}	
	
	
	var getExamMarksforReportCard = function()
	{
		if( $scope.reportCardType == 'Kindergarten' )
		{
			// set date to now 
			$scope.report.date = moment().format('YYYY-MM-DD');
			
			var params = '';
			if( $scope.isTeacher && !$scope.isClassTeacher ) params = $scope.filters.class.class_cat_id + '/true/' + $rootScope.currentUser.emp_id;
			else  params = $scope.filters.class.class_cat_id + '/true/0';
			apiService.getAllSubjects(params, loadSubjects, apiError);
		}
		else
		{
			var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
			if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
			apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);
		}
	}
	
	var loadSubjects = function(response, status)
	{
		var result = angular.fromJson(response);
		if( result.response == 'success')
		{
			if( result.nodata !== undefined )
			{
				$scope.error = true;
				$scope.errMsg = "There are no subjects assigned to this students class.";
			}
			else
			{

				$scope.showReportCard = true;
				$scope.reportData = {};
				$scope.reportData.subjects = result.data;
				
				/* look for saved report card data, if it was not passed in  */
				if( $scope.savedReportData === undefined )
				{
					var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id
					apiService.getStudentReportCard(params,loadReportCard, apiError);				
				}
				else
				{
					
					// recreated a report, carry over the comments
					$scope.comments = angular.copy($scope.savedReportData.comments) || {};
					
					angular.forEach( $scope.reportData.subjects, function(item,key){
				
						// get matching element of currentReportData 
						var orgData = $scope.savedReportData.subjects.filter(function(newItem){
							if( newItem.subject_name == item.subject_name ) return newItem;
						})[0];
						if( orgData !== undefined ) 	item.remarks = angular.copy(orgData.remarks);
					});
					
		
					//diffExamMarks($scope.latestExamMarks, $scope.savedReportData);
				}

			}
			
		}
		
			
	}

	var loadExamMarks = function(response, status)
	{
		var result = angular.fromJson(response);
		if( result.response == 'success')
		{
			if( result.details === false )
			{
				$scope.error = true;
				$scope.errMsg = "No data found.";
			}
			else
			{

				$scope.showReportCard = true;
				
				buildReportBody(result.data);	
				
				/* look for saved report card data, if it was not passed in  */
				if( $scope.savedReportData === undefined )
				{
					var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id
					apiService.getStudentReportCard(params,loadReportCard, apiError);				
				}
				else
				{
					diffExamMarks($scope.latestExamMarks, $scope.savedReportData);
				}
				/*
				$scope.report.class_name = $scope.currentFilters.class.class_name;
				$scope.report.class_id = $scope.currentFilters.class.class_id;
				$scope.report.term = $scope.currentFilters.term.term_name;
				$scope.report.term_id = $scope.currentFilters.term.term_id;
				$scope.report.year = $scope.currentFilters.term.year;
				$scope.report.teacher_id = $scope.currentFilters.class.teacher_id;
				$scope.report.teacher_name = $scope.currentFilters.class.teacher_name;
				*/
				
			}
		}
	}
	
	var buildReportBody = function(data)
	{

		$scope.examMarks = data.details;
		$scope.overallSubjectMarks = data.subjectOverall;
		$scope.overall = data.overall;
		$scope.overallLastTerm = data.overallLastTerm;	
				
		/* remove any exam types that have not been used for this report card */
		$scope.examTypes = $scope.rawExamTypes.filter(function(item){						
			var found = $scope.examMarks.filter(function(item2){
				if( item.exam_type == item2.exam_type ) return item2;
			})[0];
			if( found !== undefined ) return item;
		});		
		 
		/* group the results by subject */				
		$scope.reportData = {};
		$scope.reportData.subjects = groupExamMarks( $scope.examMarks );
		$scope.latestExamMarks = angular.copy($scope.reportData);
		
		// set overall
		var total_marks = 0;
		var total_grade_weight = 0;
		//$scope.total_overall_mark = 0;
		angular.forEach( $scope.reportData.subjects, function(item,key){

			var overall = $scope.overallSubjectMarks.filter(function(item2){
				if( item.subject_name == item2.subject_name ) return item2;
			})[0];
			
			if( overall )
			{
				item.overall_mark = overall.percentage;
				item.overall_grade = overall.grade;
				item.position = overall.rank;
				
				total_marks += parseInt(overall.total_mark);
				total_grade_weight += parseInt(overall.total_grade_weight);
			}
			
		});					
		
		
		// set totals, only add up parent subjects
		$scope.totals = {};
		angular.forEach( $scope.reportData.subjects, function(item,key)
		{						
			angular.forEach( item.marks, function(item2,key)
			{			
				if( $scope.totals[key] === undefined ) $scope.totals[key] = {total_mark:0, total_grade_weight:0};
				if( item.parent_subject_name == null ) $scope.totals[key].total_mark += item2.mark;
				if( item.parent_subject_name == null ) $scope.totals[key].total_grade_weight += item2.grade_weight;
			});						
		});
		
		
		if( $scope.originalData !== undefined )
		{
			// recreated a report, carry over the comments
			$scope.comments = angular.copy($scope.originalData.comments) || {};
			
			angular.forEach( $scope.reportData.subjects, function(item,key){
		
				// get matching element of currentReportData 
				var orgData = $scope.originalData.subjects.filter(function(newItem){
					if( newItem.subject_name == item.subject_name ) return newItem;
				})[0];
				if( orgData !== undefined ) 	item.remarks = angular.copy(orgData.remarks);
			});
		}
		
	}
	
	var groupExamMarks = function(data)
	{
		/* group the results by subject */
		var reportData = {};
		reportData.subjects = [];
		var lastSubject = '',
			marks = {},
			i = 0;
		angular.forEach(data, function(item,key){
		
			if( item.subject_name != lastSubject )
			{
				// changing to new subject, store the marks
				if( i > 0 ) reportData.subjects[(i-1)].marks = marks;
				
				reportData.subjects.push(
					{
						subject_name: item.subject_name,
						parent_subject_name: item.parent_subject_name,
						teacher_id: item.teacher_id,
						initials: item.initials
					}
				);
				
				marks = {};
				i++;
			}
			

			marks[item.exam_type] = {
				mark: item.mark,
				grade_weight: item.grade_weight,
				position: item.rank,
				grade: item.grade							
			}							
			
			
			lastSubject = item.subject_name;
			
		});
		if( reportData.subjects[(i-1)] !== undefined ) reportData.subjects[(i-1)].marks = marks;
		
		return reportData.subjects;
	}
		
	var loadReportCard = function(response, status)
	{
		var result = angular.fromJson(response);
		if( result.response == 'success')
		{

			if( result.nodata === undefined )
			{
				/* existing report card was found, load the data */
				$scope.canPrint = true;
				$scope.showReportCard = true;
				$scope.savedReport = true;
				$scope.canDelete = ( $scope.isTeacher ? false : true);
		
				$scope.report = result.data;
				$scope.report.term = $scope.report.term_name;
				$scope.savedReportData = ( result.data.report_data !== null ? angular.fromJson(result.data.report_data) : []);
				$scope.originalData = angular.copy($scope.savedReportData);
						
				$scope.setReportCardData($scope.savedReportData);
				//filterExamTypes();

				
				/* look for adjustments to exam marks */

				if( $scope.reportCardType != 'Kindergarten' )
				{
					diffExamMarks($scope.latestExamMarks, $scope.savedReportData);
					//var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
					//if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
					//apiService.getExamMarksforReportCard(params, diffExamMarks, apiError);
				}
				 
			}			
			else
			{
				/* no existing report card, go get the students exam marks and build */
				$scope.savedReport = false;
			}
		}
	}
		
	var diffExamMarks = function(currentExamMarks,savedReportData)
	{
		//var currentExamMarks = result.data.details;
		//var currentOverall = result.data.overall; // overall is now always coming from the latest exam marks

		$scope.currentReportData = {};
		$scope.currentReportData.subjects = groupExamMarks( currentExamMarks );
		
		/* compare to stored report that is currently loaded */
		$scope.differences = [];
		angular.forEach( savedReportData.subjects, function(item,key){
			
			/* get matching element of currentReportData */
			var curData = $scope.currentReportData.subjects.filter(function(newItem){
				if( newItem.subject_name == item.subject_name ) return newItem;
			})[0];

			if( curData !== undefined )
			{
				angular.forEach( item.marks, function(mark,examType){
					
					if( curData.marks[examType].mark != mark.mark )
					{
						$scope.differences.push({
							subject_name: item.subject_name,
							exam_type: examType,
							change : 'Mark has changed from ' + mark.mark + ' to ' + curData.marks[examType].mark
						});
					}
					if( curData.marks[examType].position != mark.position )
					{
						$scope.differences.push({
							subject_name: item.subject_name,
							exam_type: examType,
							change : 'Position has changed from ' + mark.position + ' to ' + curData.marks[examType].position
						});
					}
					if( curData.marks[examType].grade_weight != mark.grade_weight )
					{
						$scope.differences.push({
							subject_name: item.subject_name,
							exam_type: examType,
							change : 'Grade Weight has changed from ' + mark.grade_weight + ' to ' + curData.marks[examType].grade_weight
						});
					}
					
				});
			}
		});
		
		/* compare overall standing			
		if( currentOverall.rank != $scope.overall.rank )
		{
			$scope.differences.push({
				change : 'Students rank has changed from ' + $scope.overall.rank + ' to ' + currentOverall.rank
			});
		}
		if( currentOverall.position_out_of != $scope.overall.position_out_of )
		{
			$scope.differences.push({
				change : 'Position Out Of has changed from ' + $scope.overall.position_out_of + ' to ' + currentOverall.position_out_of
			});
		}
		 */		
			
		
	}
	
	var filterExamTypes = function()
	{
		/* get a list of the exam types used on report card */
		var examTypesUsed = [];
		angular.forEach($scope.reportData.subjects, function(item,key){
			angular.forEach(item.marks, function(data,examType){
				if( examTypesUsed.indexOf(examType) === -1 ) examTypesUsed.push(examType);
			})
		});
		
		/* remove any exam types that have not been used for this report card */
		$scope.examTypes = $scope.rawExamTypes.filter(function(item){						
			var found = (examTypesUsed.indexOf(item.exam_type) > -1 ? true : false);
			if( found ) return item;
		});	
	}
	
	$scope.recreateReport = function()
	{
		/* force new report to be generated */
		$scope.canPrint = false;
		$scope.modified = true;
		$scope.getProgressReport(true);
	}	
	
	$scope.revertReport = function()
	{
		$scope.canPrint = true;
		$scope.modified = false;
		$scope.recreated = false;
		$scope.savedReport = true;
		$scope.getProgressReport();
	}
	
	$scope.setReportCardData = function(data)
	{		

		$scope.canPrint = true;
		$scope.showReportCard = true;
			
		//$scope.overall = angular.copy(data.position);		
		//$scope.overallLastTerm = angular.copy(data.position_last_term);		
		//$scope.total_overall_mark = angular.copy( $scope.reportData.total_overall_mark);
		//$scope.totals = angular.copy(data.totals);
		
		$scope.comments = angular.copy(data.comments) || {};
		
		$scope.nextTermStartDate = angular.copy(data.nextTerm);
		$scope.currentTermEndDate = angular.copy(data.closingDate);
		
		/* if this is a teacher and not the class teacher, only display their subjects */
		if( $scope.isTeacher && !$scope.isClassTeacher )
		{
			// filter $scope.reportData to teacher subjects
			$scope.reportData.subjects = $scope.reportData.subjects.filter(function(item){
				if( item.teacher_id == $rootScope.currentUser.emp_id) return item;
			});
		}
		
		/* merge saved data into report */		
		$scope.reportData.subjects = $scope.reportData.subjects.map(function(item){
			
			/* if the saved data has the same subject, set its data in report data */
			var savedItem = data.subjects.filter(function(item2){
				if( item.subject_name == item2.subject_name ) return item2;
			})[0];

			if( savedItem !== undefined )
			{
				item = savedItem;
				item.updated = true;
			}
			
			return item;
			
		});

	}
	
	$scope.updateReport = function()
	{
		$scope.canPrint = false;
		$scope.modified = true;
	}
	
	$scope.print = function()
	{
		var criteria = {
			student : $scope.student,
			report: $scope.report,
			overall: $scope.overall,
			overallLastTerm: $scope.overallLastTerm,
			examTypes: $scope.examTypes,
			reportData: $scope.reportData,
			totals: $scope.totals,
			comments: $scope.comments,
			nextTermStartDate: $scope.nextTermStartDate,
			currentTermEndDate: $scope.currentTermEndDate,
			report_card_type: $scope.reportCardType
		}

		var domain = window.location.host;
		var newWindowRef = window.open('http://' + domain + '/#/exams/report_card/print');
		newWindowRef.printCriteria = criteria;
	}
	
	$scope.deleteReportCard = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to delete this report card? <br><br><b><i>(THIS CAN NOT BE UNDONE)</i></b>',{size:'sm'});
		dlg.result.then(function(btn){
			apiService.deleteReportCard($scope.report.report_card_id, function(response,status,params){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					if( $scope.adding )
					{
						$scope.showReportCard = false;
						$scope.report = {};
						$scope.overall = {};
						$scope.overallLastTerm = {};
						$scope.reportData = undefined;
						$scope.comments = {};
						$scope.recreated = false;		
						$scope.canDelete = false;					
					}
					else
					{
						$uibModalInstance.dismiss('canceled');  
					}
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}
				
			}, apiError);

		});
	}
		
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	$scope.save = function()
	{

		$scope.reportData.position = $scope.overall;
		$scope.reportData.position_last_term = $scope.overallLastTerm;
		$scope.reportData.totals = $scope.totals;
		$scope.reportData.comments = $scope.comments;
		$scope.reportData.nextTerm = $scope.nextTermStartDate;
		$scope.reportData.closingDate = $scope.currentTermEndDate;

		/* if subject teacher, and there is an existing report card, need to update only the subject they are associated with */
		if( $scope.isTeacher && !$scope.isClassTeacher )
		{
			
			var reportData = angular.copy($scope.reportData);
			var addIt = false;
			angular.forEach( $scope.originalData.subjects, function(item,key){
				/* if saved data had subject data that this user could not view, add it back in, then save */
				angular.forEach( reportData.subjects, function(item2,key){
					addIt = ( item.subject_name == item2.subject_name ? false : true );
				});
				if( addIt )
				{
					reportData.subjects.push(item);
				}
			});

		}
		else
		{
			var reportData = angular.copy($scope.reportData);
		}
		
		var data = {
			user_id: $rootScope.currentUser.user_id,
			student_id: $scope.student.student_id,
			term_id : $scope.report.term_id,
			class_id : $scope.report.class_id,
			report_card_type : $scope.reportCardType,
			teacher_id : $scope.report.teacher_id,
			report_data : JSON.stringify(reportData)
		}

		apiService.addReportCard(data,createCompleted,apiError);			
		
	}
	
	var createCompleted = function ( response, status, params ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			/* update the original data with saved data */
			$scope.originalData = angular.copy($scope.reportData);
			$scope.canPrint = true;
			$scope.saved = true;
			$scope.modified = false;
			//$uibModalInstance.close();
			var msg = ($scope.edit ? 'Report Card was updated.' : 'Report Card was added.');
			$rootScope.$emit('reportCardAdded', {'msg' : msg, 'clear' : true});
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
		$scope.errMsg = result.data;
	}
	
} ]);