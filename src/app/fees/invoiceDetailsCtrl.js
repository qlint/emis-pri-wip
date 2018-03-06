'use strict';

angular.module('eduwebApp').
controller('invoiceDetailsCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data', '$parse',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $parse){

	$scope.invoice = data;
	$scope.date = {startDate: $scope.invoice.inv_date};
	$scope.due_date = {startDate: $scope.invoice.due_date};
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	$scope.edit = ( $scope.permissions.fees.invoices.edit !== undefined ? $scope.permissions.fees.invoices.edit  : false);
	var allLineItems = undefined;
	$scope.totals = {};
	$scope.creditApplied = false;


	// can no longer edit an invoice if it is fully paid
	if( $scope.invoice.balance == 0 && $scope.invoice.total_paid > 0 ) $scope.edit = false;

	$scope.$watch('invoice.newItem',function(newVal,oldVal){

		if( newVal == oldVal ) return;

		var index = $scope.invoiceLineItems.length - 1;
		//newVal.amount = newVal.amount * newVal.frequency;
		newVal.adding = true;
		$scope.invoiceLineItems[index] = newVal;

		$scope.sumInvoice();
	});

	$scope.initializeController = function()
	{
		apiService.getInvoiceDetails($scope.invoice.inv_id, function(response){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.invoiceLineItems = ( result.nodata ? {} : result.data );
				$scope.invoiceLineItems = $scope.invoiceLineItems.map(function(item){
					item.adding = false;
					return item;
				});
				allLineItems = angular.copy($scope.invoiceLineItems);

				$scope.totals.balance = angular.copy($scope.invoice.balance);
				$scope.totals.total_due = angular.copy($scope.invoice.total_due);

			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}

		}, apiError);

		var params = $scope.invoice.student_id + '/' + moment($scope.invoice.inv_date).format('YYYY-MM-DD');
		apiService.getStudentArrears(params, function(response){
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

		apiService.getTerms(undefined, function(response,status)
		{
			var result = angular.fromJson(response);
			if( result.response == 'success' && !result.nodata )
			{
				$scope.terms = result.data;
			}
		}, apiError);
	}
	$scope.initializeController();

	$scope.applyCredit = function()
	{
		$scope.creditApplied = !$scope.creditApplied;
		var invoiceTotal = angular.copy($scope.totals.total_due);

		// credit is applied
		if( $scope.creditApplied )
		{
			// if credit is larger than the invoice total, only apply as much as invoice
			if( invoiceTotal < $scope.credit )
			{
				$scope.appliedCreditAmt = invoiceTotal;
				$scope.creditAvailable = $scope.credit - invoiceTotal;
				$scope.totals.balance = invoiceTotal - $scope.appliedCreditAmt;
			}
			else
			{
				$scope.appliedCreditAmt = $scope.credit;
				$scope.creditAvailable = 0;
				$scope.totals.balance = invoiceTotal - $scope.credit;
			}
		}
		else
		{
			// credit is not applied
			$scope.totals.balance = invoiceTotal;
			$scope.creditAvailable = $scope.credit;
			$scope.appliedCreditAmt = 0;
		}

	}

	$scope.cancel = function()
	{
		$uibModalInstance.dismiss($scope.updateFeeItems);
	}; // end cancel

	$scope.cancelInvoice = function()
	{
		var dlg = $dialogs.confirm('Cancel Invoice', 'Are you sure you want to mark this invoice as <strong>canceled</strong>?', {size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id: $scope.currentUser.user_id,
				inv_id:$scope.invoice.inv_id
			};
			apiService.cancelInvoice(data,  function(response){
				var result = angular.fromJson(response);

				if( result.response == 'success')
				{
					$scope.invoice.canceled = true;
					$rootScope.$emit('invoiceAdded', {'msg' : 'Invoice canceled.', 'clear' : true});
				}

			}, apiError);
		});
	}

	$scope.reactivateInvoice = function()
	{
		var dlg = $dialogs.confirm('Activate Invoice', 'Are you sure you want to mark this invoice as <strong>active</strong>?', {size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id: $scope.currentUser.user_id,
				inv_id:$scope.invoice.inv_id
			};

			apiService.reactivateInvoice(data,  function(response){
				var result = angular.fromJson(response);

				if( result.response == 'success')
				{
					$scope.invoice.canceled = false;
					$rootScope.$emit('invoiceAdded', {'msg' : 'Invoice activated.', 'clear' : true});
				}

			}, apiError);
		});
	}

	$scope.deleteInvoice = function()
	{
		var dlg = $dialogs.confirm('Delete Invoice', 'Are you sure you want to <strong>DELETE</strong> this invoice? (This CAN NOT be undone)', {size:'sm'});
		dlg.result.then(function(btn){

			apiService.deleteInvoice($scope.invoice.inv_id,  function(response){
				var result = angular.fromJson(response);

				if( result.response == 'success')
				{
					$rootScope.$emit('invoiceAdded', {'msg' : 'Invoice deleted.', 'clear' : true});
					$uibModalInstance.close($scope.updateFeeItems);
				}

			}, apiError);
		});
	}

	$scope.removeLineItem = function(index)
	{
		$scope.invoiceLineItems.splice(index,1);
		$scope.sumInvoice();
		$scope.changes = true;
	}

	$scope.revertInvoice = function(index)
	{
		$scope.invoiceLineItems = allLineItems;
		$scope.sumInvoice();
		$scope.changes = false;
	}

	$scope.sumInvoice = function()
	{
		$scope.changes = true;
		$scope.totals.total_due = $scope.invoiceLineItems.reduce(function(sum,item){
			if( item.amount == '' ) item.amount = 0;
			sum = sum + parseFloat(item.amount);
			return sum;
		},0);
		$scope.totals.balance = $scope.invoice.total_paid - $scope.totals.total_due;
		$scope.invoice.balance = angular.copy($scope.totals.balance);

		if( $scope.creditApplied ) $scope.totals.balance = $scope.totals.balance - $scope.credit;
	}

	$scope.addRow = function(forceRefresh)
	{
		if( $scope.studentFeeItems === undefined || forceRefresh )
		{
			apiService.getStudentFeeItems($scope.invoice.student_id,function(response,status){

				var result = angular.fromJson(response);

				if( result.response == 'success')
				{
					$scope.studentFeeItems = angular.copy(result.data);
				}


			},apiError);
		}
		$scope.invoiceLineItems.push({
			fee_item:undefined,
			amount:undefined,
			notselected:true
		});
	}

	$scope.viewStudent = function(student,index)
	{
		$scope.removeLineItem(index);

		var domain = window.location.host;
		var data = {
			student: student,
			section : 'fee_items'
		};
		var dlg = $dialogs.create('http://localhost:8008/highschool/app/students/viewStudent.html','viewStudentCtrl',data,{size: 'lg',backdrop:'static'});
		dlg.result.then(function(results){
			// refresh invoice preview
			$scope.updateFeeItems = results;
			$scope.addRow(true);

		},function(){
			$scope.updateFeeItems = results;
			$scope.addRow(true);
		});

	}

	$scope.printInvoice = function()
	{
		// get the student and invoice line items
		apiService.getStudentDetails($scope.invoice.student_id, function(response){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				var student = $rootScope.formatStudentData([result.data]);

				// set current class to full class object
				var currentClass = $rootScope.allClasses.filter(function(item){
					if( item.class_id == student[0].class_id ) return item;
				});

				student[0].current_class = currentClass[0];

				$scope.student = student[0];

				// open up invoice
				var data = {
					student: $scope.student,
					invoice: $scope.invoice
				}
				$dialogs.create('/app/fees/invoice.html','invoiceCtrl',data,{size: 'md',backdrop:'static'});
			}
		});
	}

	$scope.save = function()
	{
		$scope.error = false;
		$scope.errMsg = '';


		var lineItems = [];

		angular.forEach($scope.invoiceLineItems, function(item,key){
			lineItems.push({
				student_fee_item_id: item.student_fee_item_id,
				inv_item_id: item.inv_item_id,
				amount: item.amount
			});
		});

		var data = {
			user_id: $scope.currentUser.user_id,
			inv_id: $scope.invoice.inv_id,
			total_amount: $scope.totals.total_due,
			inv_date: moment($scope.date.startDate).format('YYYY-MM-DD'),
			due_date: moment($scope.due_date.startDate).format('YYYY-MM-DD'),
			line_items: lineItems,
			term_id: $scope.invoice.term_id
		};


		apiService.updateInvoice(data,createCompleted,apiError);

	}

	var createCompleted = function(response,status)
	{
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			if( $scope.creditApplied )
			{
				showCreditApplyForm(result.data);
			}
			else
			{
				$uibModalInstance.close($scope.updateFeeItems);
				$rootScope.$emit('invoiceAdded', {'msg' : 'Invoice updated.', 'clear' : true});
			}

		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}

  var showCreditApplyForm = function(data)
	{
		// display the invoice line items for the user to choose for applying payment
		var creditRemaining = $scope.appliedCreditAmt;

		var data = {
			selectedStudent:data,
			invoiceData:data,
			appliedCreditAmt: $scope.appliedCreditAmt,
			payments: $scope.availableCredits
		};
		var size = ( $scope.availableCredits > 1 ? 'lg' : 'md' );
		var dlg = $dialogs.create('applyCredit.html','applyCreditCtrl',data,{size: size,backdrop:'static'});
		dlg.result.then(function(results){
			// saved, close it all down
				$uibModalInstance.close($scope.updateFeeItems);
				$rootScope.$emit('invoiceAdded', {'msg' : 'Invoice updated.', 'clear' : true});
		},function(){
			// user cancelled, now what?
			// ask them if they do not wish to apply the credit?
			var dlg2 = $dialogs.confirm('Cancel Credit?','Do you wish to cancel applying the credit to this invoice?', {size:'sm'});
			dlg2.result.then(function(btn){
				// they want to cancel, close window
				$uibModalInstance.close($scope.updateFeeItems);
				$rootScope.$emit('invoiceAdded', {'msg' : 'Invoice updated.', 'clear' : true});
			},function(btn){
				// if they so no, they need to select the fee items
				showCreditApplyForm(data);
			});
		});
	}

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}

}]);
