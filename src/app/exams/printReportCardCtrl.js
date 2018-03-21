'use strict';

angular.module('eduwebApp').
controller('printReportCardCtrl', ['$scope', '$rootScope',
function($scope, $rootScope){

	var initializeController = function()
	{

		var loadStreamPOsition = function(response, status)
		{
			var result = angular.fromJson(response);
			console.log("streamPosition - >");
			console.log(response);
				$scope.streamRankPosition = result.data.streamRank[0].position;
			$scope.streamRankOutOf = result.data.streamRank[0].position_out_of;

				console.log($scope.streamRankPosition);
				console.log($scope.streamRankOutOf);

		}

		$scope.isAdmin = ( $rootScope.currentUser.user_type == 'SYS_ADMIN' ? true : false );

		var data = window.printCriteria;
		var getPrintRank = localStorage.getItem("printStreamRank");
		var getStreamRankOutOf = localStorage.getItem("printStreamRankOutOf");
		var getClassPos = localStorage.getItem("printClassPos");
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
		$scope.graphPoints = angular.fromJson(data.graphPoints);
		$scope.nextTermStartDate = data.nextTermStartDate;
		$scope.currentTermEndDate = data.currentTermEndDate;
		//$scope.total_overall_mark = data.total_overall_mark;
		$scope.reportCardType = data.report_card_type;
		$scope.chart_path = data.chart_path;
		$scope.motto = data.motto;
		$scope.currentClassPosition = data.currentClassPosition;
		$scope.streamRankPosition = data.streamRankPosition;
		$scope.streamRankOutOf = data.streamRankOutOf;
		// $scope.currentClassPosition.position = getClassPos;
		// $scope.streamRankPosition = getPrintRank;
		// $scope.streamRankOutOf = getStreamRankOutOf;


		$scope.loading = false;

		setTimeout( function(){
			window.print();

			setTimeout( function(){
				$rootScope.isPrinting = false;
				window.close();
			}, 100);
		}, 500);

	}
	setTimeout(initializeController,1);

	$scope.$on('$destroy', function() {
		$rootScope.isPrinting = false;
    });



} ]);
