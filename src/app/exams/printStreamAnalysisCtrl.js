'use strict';

angular.module('eduwebApp').
controller('printStreamAnalysisCtrl', ['$scope', '$rootScope',
function($scope, $rootScope ){


	var initializeController = function()
	{
		var data = window.printCriteria;
		console.log(data);
		var criteria =  angular.fromJson(data.criteria);
		$scope.title = criteria.class_name + ' ' + criteria.term + ' ' + criteria.exam_type;
		$scope.tableHeader = angular.fromJson(data.tableHeader);
		$scope.examMarks = angular.fromJson(data.examMarks);
		$scope.totalMarks = angular.fromJson(data.totalMarks);
		$scope.totalStudents = $scope.examMarks.length;

		setTimeout( function(){
			window.print();

			setTimeout( function(){
				$rootScope.isPrinting = false;
				window.close();
			}, 100);
		}, 100);
	}
	setTimeout(initializeController,1);

	$scope.displayMeanScore = function(key)
	{
		return Math.round($scope.totalMarks[key]/$scope.totalStudents,1) || '-' ;
	}


} ]);
