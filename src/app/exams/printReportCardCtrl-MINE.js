'use strict';

angular.module('eduwebApp').
controller('printReportCardCtrl', ['$scope', '$rootScope',
function($scope, $rootScope){

	var initializeController = function()
	{
		$scope.isAdmin = ( $rootScope.currentUser.user_type == 'SYS_ADMIN' ? true : false );

		// var dataURL = localStorage.getItem("theImgExport");
		// localStorage.getItem("theImgExport");
		var canvas = document.getElementById('line1');
		var dataURL = canvas.toDataURL('image/png', 1.0);
		function done(){
			var canvas = document.getElementById('line1');
			var dataURL = canvas.toDataURL('image/png', 1.0);
			// document.getElementById("url").src=dataURL;
			// document.getElementById('printchart').setAttribute('src', dataURL);

			localStorage.setItem("theImgExport", dataURL);
			var exportChartImg = localStorage.setItem("theImgExport", dataURL);
			dataURL = localStorage.getItem("theImgExport");
		}

			function initChart1(ctx,lineChartData)
			{

				window.myLine1 = Chart.Line(ctx, {
					type: 'line',
					data: lineChartData,
					options: {
						responsive: true,
						hoverMode: 'single',
						animation: {
							onComplete: done
						},
						stacked: false,
						scales: {
							xAxes: [{
								display: true,
								labels: {
									fontFamily: "Arial",
									userCallback: function(tickValue, index, tickArray) {

										var interval = Math.round( lineChartData.labels.length / 15);
										if( lineChartData.labels.length > 15 )
										{
											if( index%interval === 0 ) return tickValue
											else return '';
										}
										else
										{
											return tickValue
										}
									},
								}
							}],
							yAxes: [{
								display: true,
								beginAtZero: true,
								labels: {
									fontFamily: "Arial",
								}
							}],
						}
					}
				});

			}


		var data = window.printCriteria;
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
		$scope.performanceChart = document.getElementById("onPrintOnly");
		$scope.performanceChart.innerHTML += '<div class="main-chart-container" id="line1Chart" ng-model="graphPoints" name="graphPoints" ng-show="isPrinting"><canvas id="line1" class="chart chart-line efficiency-chart"></canvas></div>';
		$scope.nextTermStartDate = data.nextTermStartDate;
		$scope.currentTermEndDate = data.currentTermEndDate;
		//$scope.total_overall_mark = data.total_overall_mark;
		$scope.reportCardType = data.report_card_type;

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
