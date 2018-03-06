'use strict';

angular.module('eduwebApp').
controller('invoiceFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data', '$parse',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $parse){

	$scope.student = {};
	$scope.selectedStudent = ( data.selectedStudent !== undefined ? data.selectedStudent : undefined);
	$scope.selectStudent = ( data.selectedStudent !== undefined ? false : true);
	$scope.student.selected = $scope.selectedStudent;
	$scope.invoice = {};
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	$scope.invoice.date = {startDate: moment().format('YYYY-MM-DD')};
	$scope.invoice.due_date = {startDate: moment().add(1,'months').format('YYYY-MM-DD')};
	$scope.invoiceLineItems = [];
	$scope.totals = {};
	$scope.alert = {};
	$scope.filters = {};
	$scope.filters.method = 'system';
	$scope.creditApplied = false;

	$scope.initializeController = function()
	{
		if( $scope.selectedStudent === undefined )
		{
			apiService.getAllStudents(true, function(response){
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

			}, function(){});
		}

		apiService.getTerms(undefined, function(response,status)
		{
			var result = angular.fromJson(response);
			if( result.response == 'success' && !result.nodata )
			{
				$scope.terms = result.data;
				$rootScope.terms = result.data;

				var currentTerm = $scope.terms.filter(function(item){
					if( item.current_term ) return item;
				})[0];
				$scope.filters.term_id = currentTerm.term_id;
			}
		}, apiError);
	}
	$scope.initializeController();

	$scope.cancel = function()
	{
		$uibModalInstance.dismiss($scope.updateFeeItems );
	}; // end cancel

	$scope.clearSelect = function(item, $event)
	{
		$event.stopPropagation();

		var item = $parse(item + ".selected");
			item.assign($scope, undefined);
	};

	$scope.$watch('student.selected', function(newVal,oldVal){
		if( newVal == oldVal ) return;

		// grab what should be invoice for next term
		$scope.selectedStudent = $scope.student.selected;

	});

	$scope.viewStudent = function(student)
	{
		var domain = window.location.host;
		var data = {
			student: student,
			section : 'fee_items'
		};
		var dlg = $dialogs.create('http://localhost:8008/highschool/app/students/viewStudent.html','viewStudentCtrl',data,{size: 'lg',backdrop:'static'});
		dlg.result.then(function(results){
			// refresh invoice preview
			$scope.updateFeeItems = results;
			$scope.generateInvoice();
		},function(){
			$scope.generateInvoice();
		});
	}

	$scope.generateInvoice = function()
	{
		if( $scope.selectedStudent === undefined || $scope.selectedStudent.student_id === undefined )
		{
			$scope.studentError = true;
		}
		else
		{
			$scope.studentError = false;
			$scope.error = false;
			$scope.errMsg = '';
			$scope.hasCredit = undefined;
			$scope.credit = undefined;
			$scope.hasArrears = undefined;
			$scope.arrears = undefined;

			$scope.loadManual = false;
			$scope.loadSystem = false;
			$scope.termId = angular.copy($scope.filters.term_id);

			apiService.getStudentCredits($scope.selectedStudent.student_id, function(response,status)
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

				if( $scope.filters.method == 'manual' )
				{
					// get student fee items if not already set
					if( $scope.selectedStudent !== undefined && $scope.studentFeeItems === undefined )
					{
						apiService.getStudentFeeItems($scope.selectedStudent.student_id,function(response,status){
							var result = angular.fromJson(response);
							if( result.response == 'success')  $scope.studentFeeItems = angular.copy(result.data);

						},apiError);
					}

					$scope.loadManual = true;
				}
				else
				{
					var params = $scope.termId + '/' + $scope.selectedStudent.student_id;
					apiService.generateInvoices(params, displayInvoice, apiError);
				}
			}, apiError);

			var params = $scope.selectedStudent.student_id + '/' + moment().format('YYYY-MM-DD');
			apiService.getStudentArrears(params, function(response){
				var result = angular.fromJson(response);
				if( result.response == 'success' && result.nodata === undefined )
				{
					$scope.arrears = result.data.balance;
					$scope.hasArrears = true;
				}
			}, apiError);

		}
	}

	var displayInvoice = function(response,status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.results = ( result.nodata ? [] : result.data );
			$scope.invoices = [];

			// group results by inv date
			$scope.invoices = $scope.results.reduce(function(sum, item) {
				var date = angular.copy(item.inv_date); // store it to use as our key
				item.amount = item.invoice_amount;
				item.inv_date = {startDate:moment(date).format('YYYY-MM-DD')};
				item.due_date = {startDate: moment(date).add(1,'month').format('YYYY-MM-DD')}; // put into object for date selector
				if( sum[date] === undefined ) sum[date] = [];
				sum[date].push( item );
				return sum;
			}, {});

			$scope.activeInvoice = Object.keys($scope.invoices)[0];

			// get total of each array in the object
			$scope.invoiceTotal = {};
			angular.forEach($scope.invoices, function(item,key){
				$scope.invoiceTotal[key] = item.reduce(function(sum,item){
					return sum = (sum + parseFloat(item.amount));
				},0);
			});

			$scope.invoiceLineItems = $scope.invoices[$scope.activeInvoice];
			$scope.totals.balance = angular.copy($scope.invoiceTotal[$scope.activeInvoice]);
			$scope.totals.invoice = angular.copy($scope.totals.balance);

			$scope.loadSystem = true;
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}

	$scope.getInvoice = function(key)
	{
		$scope.activeInvoice = key;
		$scope.invoiceLineItems = $scope.invoices[key];
		$scope.totals.balance = angular.copy($scope.invoiceTotal[key]);
		$scope.sumInvoice();
	}

	$scope.$watch('invoice.newItem',function(newVal,oldVal){
		if( newVal == oldVal ) return;

		var index = $scope.invoiceLineItems.length - 1;
		$scope.invoiceLineItems[index] = newVal;
		$scope.sumInvoice();
	});

	$scope.removeLineItem = function(index)
	{
		$scope.invoiceLineItems.splice(index,1);
		$scope.sumInvoice();
	}

	$scope.sumInvoice = function()
	{
		$scope.totals.balance = $scope.invoiceLineItems.reduce(function(sum,item){
			if( item.amount == '' ) item.amount = 0;
			sum = sum + parseFloat(item.amount);
			return sum;
		},0);
		$scope.totals.invoice = angular.copy($scope.totals.balance);


		// sum all totals if automatic
		if( $scope.filters.method == 'system' )
		{
			$scope.invoiceTotal = {};
			angular.forEach($scope.invoices, function(item,key){
				$scope.invoiceTotal[key] = item.reduce(function(sum,item){
					if( item.amount == '' ) item.amount = 0;
					return sum = (sum + parseFloat(item.amount));
				},0);
			});
		}

		if( $scope.creditApplied ) $scope.totals.balance = $scope.totals.balance - $scope.credit;
	}

	$scope.addRow = function()
	{
		if( $scope.studentFeeItems === undefined )
		{
			apiService.getStudentFeeItems($scope.student.selected.student_id,function(response,status){

				var result = angular.fromJson(response);

				if( result.response == 'success')
				{
					$scope.studentFeeItems = angular.copy(result.data);
				}


			},apiError);
		}
		$scope.invoiceLineItems.push({
			fee_item:undefined,
			amount:undefined
		});
	}

	$scope.applyCredit = function()
	{
		$scope.creditApplied = !$scope.creditApplied;
		var invoiceTotal = angular.copy($scope.totals.invoice);

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

	$scope.save = function()
	{
		$scope.error = false;
		$scope.errMsg = '';

		var data = {
			user_id: $scope.currentUser.user_id,
			invoices: []
		};
		var lineItems = [];

		if( $scope.filters.method == 'system' )
		{
			angular.forEach($scope.invoices, function(items,key){
				lineItems = [];
				angular.forEach(items, function(item,key2){
					if( item !== null )
					{
						lineItems.push({
							student_fee_item_id: item.student_fee_item_id,
							amount: item.amount
						});
					}
				});

				data.invoices.push( {
					inv_date: moment( items[0].inv_date.startDate ).format('YYYY-MM-DD'),
					student_id: $scope.selectedStudent.student_id,
					due_date: moment( items[0].due_date.startDate ).format('YYYY-MM-DD'),
					total_amount: $scope.invoiceTotal[key],
					line_items:lineItems,
					term_id: $scope.termId
				});
			});
		}
		else
		{
			angular.forEach($scope.invoiceLineItems, function(item,key){
				if( item !== null )
				{
					lineItems.push({
						student_fee_item_id: item.student_fee_item_id,
						amount: item.amount
					});
				}
			});

			data.invoices.push( {
				inv_date: $scope.invoice.date.startDate,
				student_id: $scope.selectedStudent.student_id,
				due_date: $scope.invoice.due_date.startDate,
				total_amount: $scope.totals.balance,
				line_items:lineItems,
				term_id: $scope.termId
			});
		}
		apiService.createInvoice(data,createCompleted,apiError);

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
				$rootScope.$emit('invoiceAdded', {'msg' : 'Invoice(s) created.', 'clear' : true});
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
		var invoiceData = data;

		var creditRemaining = $scope.appliedCreditAmt;
		showPaymentForm(invoiceData);
	}

	var showPaymentForm = function(invoiceData)
	{
		var data = {
			selectedStudent:$scope.selectedStudent,
			invoiceData:invoiceData,
			appliedCreditAmt: $scope.appliedCreditAmt,
			payments: $scope.availableCredits
		};
		var dlg = $dialogs.create('applyCredit.html','applyCreditCtrl',data,{size: 'lg',backdrop:'static'});
		dlg.result.then(function(results){
			// saved, close it all down
				$uibModalInstance.close($scope.updateFeeItems);
				$rootScope.$emit('invoiceAdded', {'msg' : 'Invoice(s) created.', 'clear' : true});
		},function(){
			// user cancelled, now what?
			// ask them if they do not wish to apply the credit?
			var dlg2 = $dialogs.confirm('Cancel Credit?','Do you wish to cancel applying the credit to this invoice?', {size:'sm'});
			dlg2.result.then(function(btn){
				// they want to cancel, close window
				$uibModalInstance.close($scope.updateFeeItems);
				$rootScope.$emit('invoiceAdded', {'msg' : 'Invoice(s) created.', 'clear' : true});
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

}])
.controller('applyCreditCtrl',['$scope','$rootScope','$uibModalInstance','dialogs','$filter','apiService','data',
function($scope,$rootScope,$uibModalInstance,$dialogs,$filter,apiService,data){

		//-- Variables --//
		$scope.student = data.selectedStudent;
		$scope.invoiceData = data.invoiceData;
		$scope.appliedCreditAmt = data.appliedCreditAmt;
		$scope.payments = data.payments;
		$scope.feeItemsSelection = {};
		$scope.apply_to_all = {};
		$scope.totalApplied = {};
		$scope.totalCredit = {};

		// set up invoice items for each payment
		angular.forEach($scope.payments, function(item,key){
			item.invoiceItems = angular.copy($scope.invoiceData);

			angular.forEach(item.invoiceItems, function(item2,key2){
				$scope.feeItemsSelection[key2] = [];
				item2.amount = undefined;
			});
			$scope.apply_to_all[key] = false;
			$scope.totalApplied[key] = 0;
			$scope.totalCredit[key] = item.amount;
		});


		//-- Methods --//
		$scope.cancel = function()
    {
			$uibModalInstance.dismiss('Canceled');
		}; // end cancel

		$scope.selectAllItems = function(key, payment)
		{
			$scope.apply_to_all[key] = !$scope.apply_to_all[key];

			if( $scope.apply_to_all[key] )
			{
				angular.forEach(payment.invoiceItems, function(feeitem,key2){
					feeitem.amount = Math.abs(feeitem.balance);
					$scope.totalApplied[key] += parseInt(feeitem.amount);
					$scope.feeItemsSelection[key].push(feeitem);
				});
			}
			else
			{
				angular.forEach(payment.invoiceItems, function(feeitem,key2){
					feeitem.amount = undefined;
					$scope.totalApplied[key] = 0;
					$scope.feeItemsSelection[key] = [];
				});
			}
			$scope.totalCredit[key] = ( payment.amount - $scope.totalApplied[key] > 0 ? payment.amount - $scope.totalApplied[key] : 0) ;

		}

		$scope.toggleFeeItems = function(key,feeitem,payment)
		{
			var id = $scope.feeItemsSelection[key].indexOf(feeitem);

			// is currently selected
			if (id > -1) {
				$scope.totalApplied[key] = $scope.totalApplied[key] - feeitem.amount;
				if( $scope.totalApplied[key] < 0 ) $scope.totalApplied[key] = 0;
				feeitem.amount = undefined;
				$scope.feeItemsSelection[key].splice(id, 1);
			}

			// is newly selected
			else {
				if( $scope.appliedCreditAmt < Math.abs(feeitem.balance) ) feeitem.amount = $scope.appliedCreditAmt;
				else feeitem.amount = Math.abs(feeitem.balance);
				$scope.totalApplied[key] += parseFloat(feeitem.amount)
				$scope.feeItemsSelection[key].push(feeitem);
			}
			$scope.totalCredit[key] = ( payment.amount - $scope.totalApplied[key] > 0 ? payment.amount - $scope.totalApplied[key] : 0) ;

		};

		$scope.done = function(theForm)
		{
			// make sure they didn't enter more than the available credit
			var grandTotalApplied = 0;
			angular.forEach($scope.totalApplied, function(item){
				grandTotalApplied += item;
			});

			if( grandTotalApplied > $scope.appliedCreditAmt )
			{
				// dialog to alert
				$dialogs.error('Amount Inconsistency','<p>You have entered <strong>' + $filter('number')(grandTotalApplied) + ' Ksh</strong> towards fee items, however to total credit amount entered was <strong>' + $filter('number')($scope.appliedCreditAmt) + ' Ksh</strong>.</p><p>Please correct, the total amount applied to fee items can not exceed the total credit amount.</p>', {size:'sm'});
			}
			else if( grandTotalApplied < $scope.appliedCreditAmt )
			{
				// still some credit remaining...
				var dlg = $dialogs.confirm('Credit Remaining','<p>You have entered <strong>' + $filter('number')(grandTotalApplied) + ' Ksh</strong> towards fee items, however to total credit amount entered was <strong>' + $filter('number')($scope.appliedCreditAmt) + ' Ksh</strong>.</p><p>Did you want to reduce the credit applied to this invoice to ' + $filter('number')(grandTotalApplied) + ' Ksh?</p>', {size:'sm'});
				dlg.result.then(function(btn){
					 // save the form
					 savePayment();
				});
			}
			else
			{
				// bingo bango, we've got the right amount
				savePayment();
			}
		}; // end save

		var savePayment = function()
		{
			angular.forEach($scope.payments, function(item,key){
				// only send an update if at least one of the fee items was selected
				if( $scope.feeItemsSelection[key].length > 0 )
				{
					var lineItems = [];
					angular.forEach($scope.feeItemsSelection[key], function(item,key){
						lineItems.push({
							payment_inv_item_id: item.payment_inv_item_id,
							inv_item_id: item.inv_item_id,
							inv_id: item.inv_id,
							amount: item.amount
						});
					});

					var data = {
						user_id: $rootScope.currentUser.user_id,
						payment_id : item.payment_id,
						student_id : $scope.student.student_id,
						payment_date : moment(item.payment_date.startDate).format('YYYY-MM-DD'),
						amount: item.amount,
						payment_method : item.payment_method,
						slip_cheque_no: item.slip_cheque_no,
						replacement_payment: (item.replacement_payment == 'true' ? 't' : 'f' ),
						line_items: lineItems,
						hasCredit: true,
						creditAmt: $scope.totalCredit[key],
						creditId: item.credit_id || null
					};

					apiService.applyCredit(data, updateComplete, apiError);
				}
			});
		}

		var updateComplete = function(response)
		{
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				$uibModalInstance.close(result.data);
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

		$scope.sumPayment = function(key)
		{
			$scope.totalApplied[key] = $scope.payments[key].invoiceItems.reduce(function(sum,item){
				if( item.amount == '' ) item.amount = 0;
				sum = sum + parseFloat(item.amount);
				return sum;
			},0);
			$scope.totalCredit[key] = ( $scope.payments[key].amount - $scope.totalApplied[key] > 0 ? $scope.payments[key].amount - $scope.totalApplied[key] : 0);
		}

	}]) // end controller(addCargoCtrl)
.run(['$templateCache',function($templateCache){
  		$templateCache.put('applyCredit.html',
			'<form name="applyCredit" class="form-horizontal modalForm" novalidate role="form" ng-submit="done(applyCredit)">' +
			'<div class="modal-header">'+
				'<h4 class="modal-title">Apply Credit</h4>' +
			'</div>' +
			'<div class="modal-body cleafix">' +
				'<div class="alert alert-info">Select where you would like to apply the credit of <b>{{appliedCreditAmt|currency:""}}</b> Ksh.</div>' +
				'<div class="row">'+
					'<div ng-class="{\'col-sm-6\': payments.length>1, \'col-sm-12\': payments.length<=1}" ng-repeat="payment in payments track by $index">'+
						'<div class="well">'+
						'<div class="row">'+
							'<div class="col-sm-4">'+
								'<label>Payment No.</label>'+
								'<p>{{payment.payment_id}} </p>'+
							'</div>'+

							'<div class="col-sm-4">'+
								'<label>Payment Date</label>'+
								'<p>{{payment.payment_date|date}} </p>'+
							'</div>'+

							'<div class="col-sm-4">'+
								'<label>Payment Method</label>'+
								'<p>{{payment.payment_method}} </p>'+
							'</div>'+

							'<div class="col-sm-4">'+
								'<label>Amount</label>'+
								'<p class="nowrap">{{payment.amount|numeric}} Ksh </p>'+
							'</div>'+

							'<div class="col-sm-4">'+
								'<label>Applied</label>'+
								'<p class="nowrap" ng-class="{\'text-danger\': totalApplied[$index]>payment.amount}">{{totalApplied[$index]|numeric}} Ksh </p>'+
							'</div>'+

							'<div class="col-sm-4">'+
								'<label>Remaining</label>'+
								'<p class="nowrap" ng-class="{\'text-success\': totalCredit[$index]>0}">{{totalCredit[$index]|numeric}} Ksh </p>'+
							'</div>'+

						'</div>'+

						'<table class="display dataTable" cellspacing="0" width="100%">'+
						'<thead>'+
							'<tr>'+
								'<th class="center">'+
									'<input type="checkbox" name="apply_to_all" ng-model="payment.apply_to_all[$index]" ng-click="selectAllItems($index,payment)" ng-value="true"  />'+
								'</th>'+
								'<th>Fee Item</th>'+
								'<th>Balance</th>'+
								'<th>Paying</th>'+
							'</tr>'+
						'</thead>'+
						'<tbody>'+
							'<tr ng-repeat="feeitem in payment.invoiceItems track by $index">'+
								'<td class="center">'+
									'<input type="checkbox" name="selected_invoices[$parent.$index][]" value="{{item.inv_item_id}}" ng-checked="feeItemsSelection[$parent.$index].indexOf(feeitem) > -1" ng-click="toggleFeeItems($parent.$index,feeitem,payment)" >'+
								'</td>'+
								'<td ng-click="toggleFeeItems($parent.$index,feeitem,payment)">{{feeitem.fee_item}}</td>'+
								'<td ng-click="toggleFeeItems($parent.$index,feeitem,payment)">{{feeitem.balance|numeric}}</td>'+
								'<td>'+
									'<input type="text" name="fee_item_amount[$parent.$index][]" ng-model="feeitem.amount" class="form-control" placeholder="{{feeitem.balance|makePositive}}" ng-change="sumPayment($parent.$index)" />'+
								'</td>'+
							'</tr>'+
						'</tbody>'+
					'</table>' +
					'</div>' +
				'</div>' +
				'</div>' +
			'</div>'+
			'<div class="modal-footer">' +
				'<button type="button" class="btn btn-link" ng-click="cancel()">Cancel</button>' +
				'<button type="submit" class="btn btn-success">Save</button>' +
			'</div>' +
			'</form>'
		);
}]);
