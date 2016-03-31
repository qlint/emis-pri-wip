'use strict';

angular.module('eduwebApp').
controller('listStudentsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window',
function($scope, $rootScope, apiService, $timeout, $window){

	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.students = [];
	$scope.filterShowing = false;
	$scope.toolsShowing = false;
	var currentStatus = true;
	var isFiltered = false;	
	$rootScope.modalLoading = false;
	
	
	var initializeController = function () 
	{
		// get classes
		if( $rootScope.allClasses === undefined )
		{
			var test = apiService.getAllClasses({}, function(response){
				var result = angular.fromJson(response);
				
				// store these as they do not change often
				if( result.response == 'success') 
				{
					$rootScope.allClasses = result.data;
					$scope.classes = $rootScope.allClasses;
					getStudents('true',false);
				}
				
			}, function(){});
		}
		else
		{
			$scope.classes = $rootScope.allClasses;
			getStudents('true',false);
		}
		

	}
	initializeController();
	
	var getStudents = function(status, filtering)
	{
		apiService.getAllStudents(status, function(response){
			var result = angular.fromJson(response);
			
			// store these as they do not change often
			if( result.response == 'success')
			{
				if( result.nodata ) var formatedResults = [];
				else {
					// make adjustments to student data
					var formatedResults = formatStudentData(result.data);
				}
					
				if( filtering )
				{
					$scope.formerStudents = formatedResults
					filterStudents();
				}
				else
				{
					$scope.allStudents = formatedResults;
					$scope.students = formatedResults;
					$timeout(initDataGrid,10);
				}
				
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
			
		}, function(){});
	}
	
	var formatStudentData = function(data)
	{
		// make adjustments to student data
		var formatedResults = data.map(function(item){
			item.student_name = item.first_name + ' ' + item.middle_name + ' ' + item.last_name;
			var theClass = $rootScope.allClasses.filter(function(a){ 
				return a.class_id == item.current_class;
			})[0];
			item.class_name = (theClass ? theClass.class_name : '');
			item.class_cat_id = (theClass ? theClass.class_cat_id : '');
			item.class_id = (theClass ? theClass.class_id : '');
			
			if( item.guardians)
			{
				item.guardians = item.guardians.map(function(parent){
					parent.parent_full_name = parent.first_name + ' ' + parent.middle_name + ' ' + parent.last_name;
					return parent;
				});
			}
			
			item.status = ( item.active ? 'Active' : 'In-Active');
			item.adoptedStr = ( item.adopted ? 'Yes' : 'No');
			item.adoptionAwareStr = ( item.adoption_aware ? 'Yes' : 'No');
			item.other_medical_conditions_str = ( item.other_medical_conditions ? 'Yes' : 'No');
			item.hospitalized_str = ( item.hospitalized ? 'Yes' : 'No');
			item.current_medical_treatment_str = ( item.current_medical_treatment ? 'Yes' : 'No');
			
			return item;
		});
		console.log(formatedResults);
		return formatedResults;
	}
	
	var initDataGrid = function() 
	{
		var tableElement = $('#resultsTable');
		$scope.dataGrid = tableElement.DataTable( {
				responsive: {
					details: {
						type: 'column'
					}
				},
				columnDefs: [ {
					className: 'control',
					orderable: false,
					targets:   0
				} ],
				paging: false,
				destroy:true,
				order: [1,'asc'],
				filter: true,
				info: false,
				sorting:[],
				initComplete: function(settings, json) {
					$scope.loading = false;
					$rootScope.loading = false;
					$scope.$apply();
				},
				language: {
						search: "Search Results<br>",
						searchPlaceholder: "Filter",
						lengthMenu: "Display _MENU_",
						emptyTable: "No students found."
				},
			} );
			
		
		var headerHeight = $('.navbar-fixed-top').height();
		//var subHeaderHeight = $('.subnavbar-container.fixed').height();
		var searchHeight = $('#body-content .content-fixed-header').height();
		var offset = ( $rootScope.isSmallScreen ? 22 : 13 );
		new $.fn.dataTable.FixedHeader( $scope.dataGrid, {
				header: true,
				headerOffset: (headerHeight + searchHeight) + offset
			} );
		
		
		// position search box
		if( !$rootScope.isSmallScreen )
		{
			var filterFormWidth = $('.dataFilterForm form').width();
			console.log(filterFormWidth);
			$('#resultsTable_filter').css('left',filterFormWidth+45);
		}
		
		$window.addEventListener('resize', function() {
			
			$rootScope.isSmallScreen = (window.innerWidth < 768 ? true : false );
			if( $rootScope.isSmallScreen )
			{
				console.log('here');
				$('#resultsTable_filter').css('left',0);
			}
			else
			{
				var filterFormWidth = $('.dataFilterForm form').width();
				console.log(filterFormWidth);
				$('#resultsTable_filter').css('left',filterFormWidth-30);	
			}
		}, false);
		
	}
	
	$scope.$watch('filters.class_cat_id', function(newVal,oldVal){
		if (oldVal == newVal) return;
		
		if( newVal === undefined || newVal == '' ) 	$scope.classes = $rootScope.allClasses;
		else
		{	
			// filter classes to only show those belonging to the selected class category
			$scope.classes = $rootScope.allClasses.reduce(function(sum,item){
				if( item.class_cat_id == newVal ) sum.push(item);
				return sum;
			}, []);
		}
	});
		
	$scope.toggleFilter = function()
	{
		$scope.filterShowing = !$scope.filterShowing;
		
		if( $scope.filterShowing || $scope.toolsShowing )
		{
			$('#resultsTable_filter').hide();
		}
		else
		{
			$timeout( function()
			{
				$('#resultsTable_filter').show()
			},500);
		}
	}
	
	$scope.toggleTools = function()
	{
		$scope.toolsShowing = !$scope.toolsShowing;
		
		if( $scope.filterShowing || $scope.toolsShowing )
		{
			$('#resultsTable_filter').hide();
		}
		else
		{
			$timeout( function()
			{
				$('#resultsTable_filter').show()
			},500);
		}
	}
	
	$scope.loadFilter = function()
	{
		$scope.loading = true;
		isFiltered = true;
		
		// if user is filtering for former students and we have not previously pulled these, get them, then continue to filter
		if( $scope.filters.status == 'false' && $scope.formerStudents === undefined )
		{
			// we need to fetch inactive students first
			getStudents('false', true);			
		}
		else
		{
			filterStudents();
		}
		
		// store the current status filter
		currentStatus = $scope.filters.status;
		
	}
	
	var filterStudents = function()
	{
		$scope.dataGrid.destroy();
		
		// filter by class category
		// allStudents holds current students, formerStudents, the former...
		var filteredResults = ( $scope.filters.status == 'false' ? $scope.formerStudents : $scope.allStudents);
		
		if( $scope.filters.class_cat_id !== undefined && $scope.filters.class_cat_id !== ''  )
		{
			filteredResults = filteredResults.reduce(function(sum, item) {
			  if( item.class_cat_id == $scope.filters.class_cat_id) sum.push(item);
			  return sum;
			}, []);
		}
		
		if( $scope.filters.class_id !== undefined && $scope.filters.class_id !== ''  )
		{
			filteredResults = filteredResults.reduce(function(sum, item) {
			  if( item.class_id == $scope.filters.class_id) sum.push(item);
			  return sum;
			}, []);
		}
		
		$scope.students = filteredResults;
		$timeout(initDataGrid,1);
	}
	
	$scope.addStudent = function()
	{
		$scope.openModal('students', 'addStudent', 'lg');
	}
	
	$scope.viewStudent = function(student)
	{
		$rootScope.modalLoading = true;
		apiService.getStudentDetails(student.student_id, function(response){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{
				var student = formatStudentData([result.data]);
				
				// set current class to full class object
				var currentClass = $rootScope.allClasses.filter(function(item){
					if( item.class_id == student[0].class_id ) return item;
				});
				
				student[0].current_class = currentClass[0];

				$scope.openModal('students', 'viewStudent', 'lg',student[0]);
			}
		});
		
		
	}
	
	$scope.$on('refreshStudents', function(event, args) {

		$scope.loading = true;
		$rootScope.loading = true;
		
		if( args !== undefined )
		{
			$scope.updated = true;
			$scope.notificationMsg = args.msg;
		}
		$scope.refresh();
		
		// wait a bit, then turn off the alert
		$timeout(function() { $scope.alert.expired = true;  }, 2000);
		$timeout(function() { 
			$scope.updated = false;
			$scope.notificationMsg = ''; 
			$scope.alert.expired = false;
		}, 3000);
	});
	
	$scope.refresh = function () 
	{
		$scope.loading = true;
		$rootScope.loading = true;
		getStudents(currentStatus,isFiltered);
	}
	
	$scope.$on('$destroy', function() {
		if($scope.dataGrid) $scope.dataGrid.destroy();
		$rootScope.isModal = false;
    });
	

} ]);