'use strict';

angular.module('eduwebApp').
controller('paymentDetailsCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data','$filter',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $filter){

	$scope.edit = ( $scope.permissions.fees.payments_received.edit !== undefined ? $scope.permissions.fees.payments_received.edit  : false);

	$scope.makeSelection = (data.payment_id === undefined ? true : false );

	$scope.student = {};
	$scope.payment = {};
	$scope.selectedPayment = data || undefined;
	$scope.editPayment =  ($scope.selectedPayment.payment_method == 'Credit' || data.noEdit ? false : true );

	$scope.dates = {};
	$scope.dates.payment_date = {startDate: $scope.selectedPayment.payment_date};

	$scope.currency = $rootScope.currentUser.settings['Currency'];
	$scope.invoiceSelection = [];
	$scope.feeItemsSelection = [];
	$scope.feeItemsSelection2 = [];
	$scope.apply_to_all = [];

	$scope.notice = (data.message !== undefined ? true : false );
	$scope.message = data.message;
//	$scope.dateSelect = {startDate: $rootScope.termRanges['Full Year'][0], endDate: $rootScope.termRanges['Full Year'][1]};

	
	var initializeController = function()
	{
		var paymentMethods = $rootScope.currentUser.settings['Payment Methods'];
		$scope.paymentMethods = paymentMethods.split(',');
		$scope.paymentMethods.push('Credit');

		// if a student was passed, get their open invoices in case use was to apply to another invoice
		// also get the details on the payment selected
		if( $scope.selectedPayment.payment_id  !== undefined )
		{
			// set variables
			$scope.student_name = angular.copy($scope.selectedPayment.student_name);
			$scope.student_id = angular.copy($scope.selectedPayment.student_id);
			
			// get details for payment
			apiService.getPaymentDetails($scope.selectedPayment.payment_id, loadPaymentDetails, apiError);

			$scope.getStudentBalance();
		}
		else
		{
			// else, user needs to select a student first, add all students
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
	}
	setTimeout(initializeController,1);

	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');
	}; // end cancel

	$scope.clearSelect = function(item, $event)
	{
		$event.stopPropagation();

		var item = $parse(item + ".selected");
			item.assign($scope, undefined);
	};

	$scope.$watch('student.selected', function(newVal,oldVal){
		if( newVal == oldVal ) return;

		$scope.selectedStudent = $scope.student.selected;
		$scope.student_name = angular.copy($scope.student.selected.student_name);
		$scope.student_id = angular.copy($scope.student.selected.student_id);

		apiService.getStudentPayments($scope.student_id, loadPayments, apiError);
		$scope.selectedPayment = undefined;
	});

	$scope.$watch('payment.selected', function(newVal,oldVal){
		if( newVal == oldVal ) return;

		$scope.selectedPayment = $scope.payment.selected;
		apiService.getPaymentDetails($scope.selectedPayment.payment_id, loadPaymentDetails, apiError);

		$scope.getStudentBalance();
	});

	$scope.$watch('selectedPayment.amount', function(newVal,oldVal){
		if( newVal == oldVal ) return;
		$scope.totalCredit = ( newVal - $scope.totalApplied > 0 ? newVal - $scope.totalApplied : 0) ;
	});

	$scope.$watch('selectedPayment.replacement_payment', function(newVal,oldVal){
		if( newVal == oldVal ) return;
		if( newVal ) getReplaceableFeeItems();
	});

	$scope.$watch('selectedPayment.invoice', function(newVal,oldVal){
		if( newVal == oldVal ) return;

		$scope.selectedNewInvoice = newVal;
	});

	$scope.getStudentBalance = function()
	{
		// get students balance for reference
		var params = $scope.student_id; // + '/' + moment($scope.dateSelect.startDate).format('YYYY-MM-DD') + '/' + moment($scope.dateSelect.endDate).format('YYYY-MM-DD');
		apiService.getStudentBalance(params, loadFeeBalance, apiError);
	}

	var loadPayments = function(response,status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.payments = ( result.nodata ? [] : angular.copy(result.data) );
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}

	var loadPaymentDetails = function(response, status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			var results = ( result.nodata ? {} : result.data );

			$scope.selectedPayment = results.payment;
			$scope.isCredit = ($scope.selectedPayment.credit_id !== null ? true : false );

			$scope.selectedInvoice = ( results.invoice.length > 0 ? formatInvoices(results.invoice) : undefined);
			$scope.paymentItems = results.paymentItems;

			apiService.getOpenInvoices($scope.student_id, loadInvoices, apiError);

			// if there is an associated invoice, set the line items
			if( $scope.selectedInvoice !== undefined )
			{
				// need to loop through the selected invoice and check off what is associated with payment
				angular.forEach( $scope.selectedInvoice, function(item0,key0){

					angular.forEach(item0.fee_items, function(item, key){

						// if a invoice item is fully paid mark it not modifiable
						// below we check if the item was paid with this payment
						// if so, then we allow modifiable
						// can not modify items that are fully paid by other payments

						angular.forEach( $scope.paymentItems, function(item2,key2)
						{
							if( item.inv_item_id == item2.inv_item_id)
							{
								item.amount = item2.line_item_amount;
								item.payment_inv_item_id = item2.payment_inv_item_id;
								item.modifiable = true;
								$scope.feeItemsSelection.push(item);
							}
						});
					});
				});

			}
			//console.log($scope.selectedInvoice);

			// if its a replacement payment, set the selected replacement items
			if( $scope.selectedPayment.replacement_payment )
			{
				getReplaceableFeeItems();
			}

			$scope.sumPayment();
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}

	var getReplaceableFeeItems = function()
	{
		if( $scope.replaceableFeeItems === undefined )
		{
			apiService.getReplaceableFeeItems($scope.student_id,function(response,status){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					$scope.replaceableFeeItems = angular.copy(result.data);

					angular.forEach( $scope.replaceableFeeItems, function(item, key){

						angular.forEach( $scope.paymentItems, function(item2,key2)
						{
							if( item.student_fee_item_id == item2.inv_item_id )
							{
								item.paying_amount = item2.line_item_amount;
								item.payment_replace_item_id = item2.payment_inv_item_id;
								$scope.feeItemsSelection2.push(item);
							}
						});
					});


				}
			},apiError);
		}
	}

	var loadInvoices = function(response,status)
	{
		$scope.loading = false;
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			var invoices = ( result.nodata ? [] : formatInvoices(angular.copy(result.data)));

			// filter out invoices already showing in Applied To
			$scope.invoices = invoices.filter(function(item){
				if( $scope.selectedInvoice )
				{
					var isMatch = $scope.selectedInvoice.filter(function(item2){
						if( item.inv_id == item2.inv_id ) return item2;
					})[0];
				}
				if( isMatch === undefined ) return item;

			});
		}
	}

	var formatInvoices = function(invoiceData)
	{
		//console.log(invoiceData);
		var currentInvoice = {};
		var currentItem = {};
		var invoices = [];
		var feeItems = []
		var overallBalance = 0;
		angular.forEach( invoiceData, function(item,key){

			if( key > 0 && currentInvoice != item.inv_id )
			{
				// store row
				invoices.push({
					inv_id: currentItem.inv_id,
					inv_date: currentItem.inv_date,
					due_date: currentItem.due_date,
					balance: currentItem.balance,
					overall_balance: overallBalance,
					total_due: currentItem.total_due,
					fee_items: feeItems,
				});

				// reset
				feeItems = [];
				overallBalance = 0;
			}

			feeItems.push({
				fee_item_id: item.fee_item_id,
				fee_item: item.fee_item,
				balance: item.balance,
				inv_item_id: item.inv_item_id,
				inv_id: item.inv_id,
				payment_id: item.payment_id,
				amount: null,
				isPaid: parseInt(item.balance) === 0 ? true : false,
				modifiable: parseInt(item.balance) === 0 ? false : true,
			});
			overallBalance += parseFloat(item.balance);

			currentInvoice = item.inv_id;
			currentItem = item;
		});
		// push in last row
		invoices.push({
			inv_id: currentItem.inv_id,
			inv_date: currentItem.inv_date,
			due_date: currentItem.due_date,
			balance: currentItem.balance,
			overall_balance: overallBalance,
			total_due: currentItem.total_due,
			fee_items: feeItems,
		});
		//console.log(invoices);
		return invoices;
	}

	var loadFeeBalance = function(response,status)
	{
		$scope.loading = false;
		var result = angular.fromJson(response);

		if( $scope.dataGrid !== undefined )
		{
			$scope.dataGrid.clear();
			$scope.dataGrid.destroy();
		}

		if( result.response == 'success')
		{
			if( result.nodata === undefined )
			{
				$scope.feeSummary = angular.copy(result.data.fee_summary);
				$scope.fees = angular.copy(result.data.fees);
				$scope.nofeeSummary = false;
			}
			else
			{
				$scope.feeSummary = [];
				$scope.fees = [];
				$scope.nofeeSummary = true;
			}

			setTimeout(initFeesDataGrid,50);
		}
	}

	var initFeesDataGrid = function()
	{
		var settings = {
			sortOrder: [4,'asc'],
			noResultsTxt: "This student has not been invoiced."
		}
		initDataGrid(settings);
	}

	var initDataGrid = function(settings)
	{

		var tableElement = $('#resultsTable3');
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
				order: settings.sortOrder,
				filter: false,
				info: false,
				sorting:[],
				scrollY:'200px',
				initComplete: function(settings, json) {
					$scope.loading = false;
					$rootScope.loading = false;
					$scope.$apply();
				},
				language: {
					lengthMenu: "Display _MENU_",
					emptyTable: settings.noResultsTxt
				},
			} );
	}

	$scope.changeInvoice = function(status)
	{
		$scope.changingInvoice = status;
		if( !status )	$scope.selectedNewInvoice = undefined;
	}

	$scope.unapplyInvoice = function()
	{
		// remove the invoice from payment
		$scope.selectedPayment.inv_id = undefined;
		$scope.selectedInvoice = undefined;
		$scope.changeInvoice( false );
	}

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}

	$scope.selectAllItems = function(key, invoice)
	{
		$scope.apply_to_all[key] = !$scope.apply_to_all[key];

		if( $scope.apply_to_all[key] )
		{
			angular.forEach(invoice.fee_items, function(feeitem,key){
				feeitem.amount = Math.abs(feeitem.balance);
				$scope.totalApplied += parseInt(feeitem.amount);
				$scope.feeItemsSelection.push(feeitem);
			});
		}
		else
		{
			angular.forEach(invoice.fee_items, function(feeitem,key){
				feeitem.amount = undefined;
				$scope.totalApplied = 0;
				$scope.feeItemsSelection = [];
			});
		}
		$scope.totalCredit = ( $scope.selectedPayment.amount - $scope.totalApplied > 0 ? $scope.selectedPayment.amount - $scope.totalApplied : 0) ;
	}

	$scope.toggleFeeItems = function(feeitem)
	{
		var id = $scope.feeItemsSelection.indexOf(feeitem);

		// is currently selected
		if (id > -1) {
			$scope.totalApplied = $scope.totalApplied - feeitem.amount;
			feeitem.amount = undefined;
			$scope.feeItemsSelection.splice(id, 1);
		}

		// is newly selected
		else {
			feeitem.amount = Math.abs(feeitem.balance);
			$scope.totalApplied += parseFloat(feeitem.amount);
			$scope.feeItemsSelection.push(feeitem);
		}

		$scope.totalCredit = ( $scope.selectedPayment.amount - $scope.totalApplied > 0 ? $scope.selectedPayment.amount - $scope.totalApplied : 0) ;
	};
	$scope.toggleFeeItems2 = function(feeitem)
	{
		var id = $scope.feeItemsSelection2.indexOf(feeitem);

		// is currently selected
		if (id > -1) {
			feeitem.paying_amount = undefined;
			$scope.feeItemsSelection2.splice(id, 1);
		}

		// is newly selected
		else {
			feeitem.paying_amount = feeitem.amount;
			$scope.feeItemsSelection2.push(feeitem);
		}
	};

	$scope.save = function(theForm)
	{
		if( !theForm.$invalid )
		{
			// make sure that the fee items selected do not total up to more than the payment amount
			var totalFeeItems = $scope.feeItemsSelection.reduce(function(sum,item){
				sum = sum + parseFloat(item.amount);
				return sum;
			},0);

			if( $scope.selectedPayment.replacement_payment !== true )
			{
				if( totalFeeItems > $scope.selectedPayment.amount )
				{
					var dlg = $dialogs.error('Amount Inconsistency','<p>You have entered <strong>' + $filter('number')(totalFeeItems) + ' Ksh</strong> towards fee items, however to total payment amount enterd was <strong>' + $filter('number')($scope.selectedPayment.amount) + ' Ksh</strong>.</p><p>Please correct, the total amount applied to fee items can not exceed the total payment amount.</p>', {size:'sm'});
				}
				else if ( totalFeeItems < $scope.selectedPayment.amount )
				{
					if( $scope.isCredit )
					{
						savePayment();
					}
					else
					{
						var dlg = $dialogs.confirm('Credit','<p>You have <strong>' +  $filter('number')(($scope.selectedPayment.amount - totalFeeItems)) + ' Ksh</strong> that has not been applied to fee items, do you wish to add this as a credit?</p>', {size:'sm'});
						dlg.result.then(function(btn){
							 // save the form
							 savePayment();

						},function(btn){
						});
					}
				}
				else
				{
					savePayment();
				}
			}
			else
			{
				savePayment();
			}
		}
	}

	var savePayment = function()
	{
		$scope.error = false;
		$scope.errMsg = '';

		if( $scope.selectedPayment.replacement_payment )
		{
			var replacementItems = [];
			angular.forEach($scope.feeItemsSelection2, function(item,key){
				replacementItems.push({
					payment_replace_item_id: item.payment_replace_item_id,
					student_fee_item_id: item.student_fee_item_id,
					amount: item.paying_amount
				});
			});

			var data = {
				user_id: $scope.currentUser.user_id,
				payment_id : $scope.selectedPayment.payment_id,
				student_id : $scope.student_id,
				payment_date : moment($scope.dates.payment_date.startDate).format('YYYY-MM-DD'),
				amount: $scope.selectedPayment.amount,
				payment_method : $scope.selectedPayment.payment_method,
				slip_cheque_no: $scope.selectedPayment.slip_cheque_no,
				replacement_payment: ($scope.selectedPayment.replacement_payment ? 't' : 'f' ),
				inv_id : ($scope.selectedPayment.invoice !== undefined ? $scope.selectedPayment.invoice.inv_id : ($scope.selectedInvoice !== undefined ? $scope.selectedInvoice.inv_id : null)),
				replacement_items: replacementItems
			};
		}
		else
		{
			var lineItems = [];
			angular.forEach($scope.feeItemsSelection, function(item,key){
				lineItems.push({
					payment_inv_item_id: item.payment_inv_item_id,
					inv_item_id: item.inv_item_id,
					inv_id: item.inv_id,
					amount: item.amount
				});
			});

			var data = {
				user_id: $scope.currentUser.user_id,
				payment_id : $scope.selectedPayment.payment_id,
				student_id : $scope.student_id,
				payment_date : moment($scope.dates.payment_date.startDate).format('YYYY-MM-DD'),
				amount: $scope.selectedPayment.amount,
				payment_method : $scope.selectedPayment.payment_method,
				slip_cheque_no: $scope.selectedPayment.slip_cheque_no,
				replacement_payment: ($scope.selectedPayment.replacement_payment == 'true' ? 't' : 'f' ),
				//inv_id : ($scope.selectedPayment.invoice !== undefined ? $scope.selectedPayment.invoice.inv_id : ($scope.selectedInvoice !== undefined ? $scope.selectedInvoice.inv_id : null)),
				line_items: lineItems,
				hasCredit: ($scope.totalCredit > 0 || $scope.isCredit ? true : false ),
				creditAmt: $scope.totalCredit,
				creditId: $scope.selectedPayment.credit_id || null
			};

		}
		apiService.updatePayment(data,createCompleted,apiError);

	}

	$scope.reversePayment = function()
	{
		var dlg = $dialogs.confirm('Reverse Payment', 'Are you sure you want to revers this payment?', {size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id: $scope.currentUser.user_id,
				payment_id:$scope.selectedPayment.payment_id
			};
			apiService.reversePayment(data,  function(response){
				var result = angular.fromJson(response);

				if( result.response == 'success')
				{
					$scope.selectedPayment.reversed = true;
					$rootScope.$emit('paymentAdded', {'msg' : 'Payment reversed.', 'clear' : true});
				}

			}, apiError);
		});
	}

	$scope.reactivatePayment = function()
	{
		var dlg = $dialogs.confirm('Activate Payment', 'Are you sure you want to mark this payment as <strong>active</strong>?', {size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id: $scope.currentUser.user_id,
				student_id: $scope.student_id,
				payment_id:$scope.selectedPayment.payment_id,
				hasCredit: ($scope.totalCredit > 0 ? true : false ),
				creditAmt: $scope.totalCredit
			};

			apiService.reactivatePayment(data,  function(response){
				var result = angular.fromJson(response);

				if( result.response == 'success')
				{
					$scope.selectedPayment.reversed = false;
					$rootScope.$emit('paymentAdded', {'msg' : 'Payment activated.', 'clear' : true});
				}

			}, apiError);
		});
	}

	$scope.deletePayment = function()
	{
		var dlg = $dialogs.confirm('Delete Payment', 'Are you sure you want to <strong>DELETE</strong> this payment? (This CAN NOT be undone)', {size:'sm'});
		dlg.result.then(function(btn){

			apiService.deletePayment($scope.selectedPayment.payment_id,  function(response){
				var result = angular.fromJson(response);

				if( result.response == 'success')
				{
					$rootScope.$emit('paymentAdded', {'msg' : 'Payment deleted.', 'clear' : true});
					$uibModalInstance.close();
				}

			}, apiError);
		});
	}

	var createCompleted = function(response,status)
	{
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			$rootScope.$emit('paymentAdded', {'msg' : 'Payment was added.', 'clear' : true});
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}

	$scope.sumPayment = function()
	{
		$scope.totalApplied = $scope.feeItemsSelection.reduce(function(sum,item){
			if( item.amount == '' ) item.amount = 0;
			sum = sum + parseFloat(item.amount);
			return sum;
		},0);
		$scope.totalCredit = ( $scope.selectedPayment.amount - $scope.totalApplied > 0 ? $scope.selectedPayment.amount - $scope.totalApplied : 0);
	}

} ]);