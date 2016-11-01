'use strict';

angular.module('eduwebApp').
controller('invoiceCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, data){
	console.log(data.invoice);
	$scope.invoice = data.invoice;
	$scope.student = data.student;
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	
	var termName = $scope.invoice.term_name;
	// we only want the number
	termName = termName.split(' ');
	$scope.invoice.term_name = termName[1];

	var initializeController = function()
	{
		var params = $scope.invoice.student_id + '/' + moment($scope.invoice.inv_date).format('YYYY-MM-DD');
		apiService.getStudentArrears(params, function(response)
		{
			var result = angular.fromJson(response);
			if( result.response == 'success' && result.nodata === undefined )
			{
				$scope.arrears = result.data.balance;
				$scope.hasArrears = true;
			}
		}, apiError);
		
		apiService.getStudentCredits($scope.invoice.student_id, function(response,status)
		{
			$scope.loading = false;
			var result = angular.fromJson(response);
			if( result.response == 'success' && result.nodata === undefined )
			{
				$scope.availableCredits = result.data;
				$scope.hasCredit = true;
				// sum of available credit
				$scope.credit = $scope.availableCredits.reduce(function(sum,item){
					return sum += parseFloat(item.amount);
				},0);
			}
		}, apiError);
		
		apiService.getInvoiceDetails($scope.invoice.inv_id, loadInvoiceDetails, apiError);
		
	}
	setTimeout(initializeController,1);
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	var loadInvoiceDetails = function(response, status)
	{
		var result = angular.fromJson(response);
			
		if( result.response == 'success')
		{
			$scope.invoiceLineItems = ( result.nodata ? {} : result.data );
			
			$scope.lineItems = [];
			var totalAmt = 0;
			var amt;
			angular.forEach( $scope.invoiceLineItems, function(item,key){
				amt = item.amount.split('.'),
				$scope.lineItems[item.fee_item] = {
					ksh: amt[0],
					cts: amt[1]
				};
				totalAmt += parseFloat(item.amount);
			});

			var amt = ( String(totalAmt).indexOf('.') > -1 ? String(totalAmt).split('.') : [totalAmt,'00']);
			$scope.totalAmtKsh = amt[0];
			$scope.totalAmtCts = amt[1];
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
	
	$scope.print = function()
	{
		var criteria = {
			student : $scope.student,
			invoice: $scope.invoice,
			invoiceLineItems : $scope.invoiceLineItems,
			totals : {
				totalAmtKsh: $scope.totalAmtKsh,
				totalAmtCts: $scope.totalAmtCts,
			},
			lineItems: $scope.lineItems
		}

		var domain = window.location.host;
		var newWindowRef = window.open('http://' + domain + '/#/fees/invoice/print');
		newWindowRef.printCriteria = criteria;
	}
	
	
} ]);