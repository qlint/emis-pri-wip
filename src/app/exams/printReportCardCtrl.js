'use strict';

angular.module('eduwebApp').
controller('printReportCardCtrl', ['$scope', '$rootScope',
function($scope, $rootScope){
	
	var initializeController = function()
	{
		var data = window.printCriteria;
		//console.log(data);
		$rootScope.isPrinting = true;
		$scope.showReportCard = true;
		$scope.student = angular.fromJson(data.student);	
		$scope.report = angular.fromJson(data.report);	
		$scope.overall = angular.fromJson(data.overall);		
		$scope.overallLastTerm = angular.fromJson(data.overallLastTerm);	
		$scope.examTypes = angular.fromJson(data.examTypes);
		$scope.reportData = angular.fromJson(data.reportData);
		$scope.totals = angular.fromJson(data.totals);
		$scope.comments = angular.fromJson(data.comments);
		$scope.nextTermStartDate = data.nextTermStartDate;
		$scope.total_overall_mark = data.total_overall_mark;
		
		$scope.loading = false;
		
		setTimeout( function(){
			window.print();
			
			setTimeout( function(){
				$rootScope.isPrinting = false;
				//window.close();
			}, 100);
		}, 100);

	}
	setTimeout(initializeController,1);
	
	$scope.$on('$destroy', function() {
		$rootScope.isPrinting = false;
    });
	
	
	
} ]);