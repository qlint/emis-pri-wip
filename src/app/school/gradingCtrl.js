'use strict';

angular.module('eduwebApp').
controller('gradingCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$filter',
function($scope, $rootScope, apiService, $timeout, $window, $filter){


	$scope.alert = {};
	$scope.loading = true;

	$scope.gridFilter = {};
	$scope.gridFilter.filterValue  = '';

	var rowTemplate = function()
	{
		return '<div class="clickable" ng-click="grid.appScope.viewGrading(row.entity)">' +
		'  <div ng-if="row.entity.merge">{{row.entity.title}}</div>' +
		'  <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>' +
		'</div>';
	}

	$scope.gridOptions = {
		enableSorting: true,
		rowTemplate: rowTemplate(),
		rowHeight:24,
		columnDefs: [
			{ name: 'Grade', field: 'grade', enableColumnMenu: false, sort: {direction:'asc'},},
			{ name: 'Grade Marks Range', field: 'mark_range', type:'date', cellFilter:'date', enableColumnMenu: false,},
			{ name: 'Comment', field: 'comment', enableColumnMenu: false,},
		],
		exporterCsvFilename: 'school-grading.csv',
		onRegisterApi: function(gridApi){
		  $scope.gridApi = gridApi;
		  $timeout(function() {
			$scope.gridApi.core.handleWindowResize();
		  });
		}
	};

	var initializeController = function ()
	{
		getGrading();

		setTimeout(function(){
			var height = $('.full-height.datagrid').height();
			$('#grid1').css('height', height);
			$scope.gridApi.core.handleWindowResize();
		},100);
	}
	$timeout(initializeController,1);

	var getGrading = function()
	{

		apiService.getGrading({}, function(response,status,params){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.grades = ( result.nodata ? [] : result.data );

				$scope.grades = $scope.grades.map(function(item){
					item.mark_range = item.min_mark + '-' + item.max_mark;
					return item;
				});

				initDataGrid($scope.grades);
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}

		}, apiError);
	}

	var initDataGrid = function(data)
	{
		$scope.gridOptions.data = data;
		$scope.loading = false;
		$rootScope.loading = false;

	}

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}

	$scope.addGrading = function()
	{
		$scope.openModal('school', 'gradingForm', 'sm');
	}

	$scope.viewGrading = function(item)
	{
		$scope.openModal('school', 'gradingForm', 'sm',item);
	}

	$scope.$on('refreshGrades', function(event, args) {

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
		getGrading();
	}

	$scope.$on('$destroy', function() {
		$rootScope.isModal = false;
    });

} ]);
