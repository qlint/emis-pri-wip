'use strict';

angular.module('eduwebApp').
controller('listReportCardsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$q','$parse',
function($scope, $rootScope, apiService, $timeout, $window, $q, $parse){

	var initialLoad = true;
	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.students = [];
	$scope.filterShowing = false;
	$scope.toolsShowing = false;
	var currentStatus = true;
	var isFiltered = false;
	$rootScope.modalLoading = false;
	$scope.alert = {};
	$scope.refreshing = false;
	$scope.getReport = "reportTable";
	$scope.loading = true;

	var initializeController = function ()
	{
		// get terms
		var year = moment().format('YYYY');
		apiService.getTerms(year, function(response){
				var result = angular.fromJson(response);

				if( result.response == 'success')
				{
					$scope.terms = result.data;
				}

			}, apiError);

		// get classes
		if( $rootScope.allClasses === undefined )
		{
			if ( $rootScope.currentUser.user_type == 'TEACHER' )
			{
				apiService.getTeacherClasses($rootScope.currentUser.emp_id, function(response){
					var result = angular.fromJson(response);

					// store these as they do not change often
					if( result.response == 'success')
					{
						$scope.classes = result.data || [];
						$scope.filters.class = $scope.classes[0];
						$scope.filters.class_id = ( $scope.classes[0] ? $scope.classes[0].class_id : null);
						$scope.getStudentReportCards();
					}

				}, apiError);

			}
			else
			{
				apiService.getAllClasses({}, function(response){
					var result = angular.fromJson(response);

					// store these as they do not change often
					if( result.response == 'success')
					{
						$scope.classes = result.data || [];
						$scope.filters.class = $scope.classes[0];
						$scope.filters.class_id = ( $scope.classes[0] ? $scope.classes[0].class_id : null);
						$scope.getStudentReportCards();
					}

				}, apiError);
			}
		}
		else
		{
			$scope.classes = $rootScope.allClasses;
			$scope.filters.class = $scope.classes[0];
			$scope.filters.class_id = $scope.classes[0].class_id;
			$scope.getStudentReportCards();
		}


	}
	$timeout(initializeController,1);


	$scope.getStudentReportCards = function()
	{
		$scope.reportCards = {};
		$scope.tableHeader = [];
		$scope.reportsNotFound = false;
		$scope.getReport = "";

		var request = $scope.filters.class.class_id;
		apiService.getAllStudentReportCards(request, loadReportCards, apiError);
	}

	var loadReportCards = function(response,status)
	{
		$scope.loading = false;
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			if( result.nodata )
			{
				$scope.reportsNotFound = true;
				$scope.errMsg = "There are currently no report cards entered for this class.";
			}
			else
			{

				$scope.rawReportCards = result.data;

				$scope.reportCards = {};

				// group the reports by student
				$scope.reportCards.students = [];
				var lastStudent = '';
				var reports = {};
				var i = 0;
				angular.forEach($scope.rawReportCards, function(item,key){

					if( item.student_id != lastStudent )
					{
						// changing to new student, store the report
						if( i > 0 ) $scope.reportCards.students[(i-1)].reports = reports;

						$scope.reportCards.students.push(
							{
								student_name: item.student_name,
								student_id: item.student_id,
								class_id: item.class_id,
								class_cat_id: item.class_cat_id,
								report_card_id: item.report_card_id,
								report_card_type: item.report_card_type,
								teacher_id: item.teacher_id,
								teacher_name: item.teacher_name,
								class_name: item.class_name,
								term_id: item.term_id,
								date: item.date,
								year: item.year,
								admission_number: item.admission_number,
								published: item.published
							}
						);

						reports = {};
						i++;

					}
					reports[item.term_name] = {
						term_id : item.term_id,
						year: item.year,
						published : item.published,
						report_card_id: item.report_card_id,
						report_card_type: item.report_card_type,
						class_name: item.class_name,
						class_id: item.class_id,
						teacher_id: item.teacher_id,
						teacher_name: item.teacher_name,
						date: item.date,
						data: item.report_data
					};

					lastStudent = item.student_id;

				});
				$scope.reportCards.students[(i-1)].reports = reports;


				$scope.getReport = "reportTable";
				$timeout(initDataGrid,100);
			}

		}
		else
		{
			$scope.reportsNotFound = true;
			$scope.errMsg = result.data;
		}
	}

	$scope.getReportCard = function(item, term_name, reportData)
	{
		var student = {
			student_id :item.student_id,
			student_name : item.student_name,
			admission_number: item.admission_number,
			class_teacher_id: item.teacher_id,
			report_card_type: item.report_card_type
		}
		var data = {
			student : student,
			report_card_id: reportData.report_card_id,
			class_name : reportData.class_name,
			class_id : reportData.class_id,
			published: reportData.published,
			term_id: reportData.term_id,
			term_name : term_name,
			year: reportData.year,
			report_card_type: reportData.report_card_type,
			teacher_id: reportData.teacher_id,
			teacher_name: reportData.teacher_name,
			date: reportData.date,
			reportData: reportData.data,
			adding: false,
			filters:{
				term:{
					term_name:term_name,
					term_id: item.term_id,
				},
				class:{
					class_id: item.class_id,
					class_cat_id: item.class_cat_id
				}
			}
		};

		$scope.openModal('exams', 'reportCard', 'lg', data);

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
						emptyTable: "No report cards found."
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
		setSearchBoxPosition();

		if( initialLoad ) setResizeEvent();

	}

	var setSearchBoxPosition = function()
	{
		if( !$rootScope.isSmallScreen )
		{
			var filterFormWidth = $('.dataFilterForm form').width();
			$('#resultsTable_filter').css('left',filterFormWidth+55);
		}
	}

	var setResizeEvent = function()
	{
		 initialLoad = false;

		 $window.addEventListener('resize', function() {

			$rootScope.isSmallScreen = (window.innerWidth < 768 ? true : false );
			if( $rootScope.isSmallScreen )
			{
				$('#resultsTable_filter').css('left',0);
			}
			else
			{
				var filterFormWidth = $('.dataFilterForm form').width();
				$('#resultsTable_filter').css('left',filterFormWidth-30);
			}
		}, false);
	}

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

	$scope.addReportCard = function()
	{
		var data = {
			classes: $scope.classes,
			terms: $scope.terms,
			filters: $scope.filters,
			adding: true
		}
		$scope.openModal('exams', 'reportCard', 'lg', data);
	}

	$scope.printAll = function()
	{
		if (navigator.appName == "Microsoft Internet Explorer")
		{
		var PrintCommand = '<object ID="PrintCommandObject" WIDTH=0 HEIGHT=0 CLASSID="CLSID:8856F961-340A-11D0-A96B-00C04FD705A2"></object>';
		document.body.insertAdjacentHTML('beforeEnd', PrintCommand);
		PrintCommandObject.ExecWB(6, -1); PrintCommandObject.outerHTML = "";
		}
		else {
			// window.print($scope.getReportCard);
			console.log($scope.getReportCard);
		}
		// function printEverything(){
		// 	window.print();
		// }
	}

	$scope.$on('refreshReportCards', function(event, args) {

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
		$scope.refreshing = true;
		$rootScope.loading = true;
		$scope.getStudentReportCards();
	}

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}

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
