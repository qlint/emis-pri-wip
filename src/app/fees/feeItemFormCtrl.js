'use strict';

angular.module('eduwebApp').
controller('feeItemFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data){

	$scope.classCatSelection = [];
	$scope.edit = ( data !== undefined ? true : false );
	$scope.item = ( data !== undefined ? data : {} );
	$scope.deleted = false;

	
	$scope.initializeController = function()
	{
	
		var frequencies = $rootScope.currentUser.settings['Frequencies'];
		$scope.frequencies = frequencies.split(',');	
		
		if( !$scope.edit )
		{
			// set defaults for radio buttons
			$scope.item.optional = false;
			$scope.item.new_student_only = false;
			$scope.item.replaceable = false;
		}
		else
		{
			// set class categories
			$scope.item.default_amount = parseFloat(data.default_amount_raw);
			$scope.classCatSelection = data.class_cats_restriction || [];

			$scope.isTransport = ( data.fee_item == 'Transport' ? true : false );
			
			if( $scope.isTransport )
			{
				// get transport routes
				apiService.getTansportRoutes({}, function(response){
					var result = angular.fromJson(response);
					
					if( result.response == 'success')
					{
						if( result.nodata !== undefined) 
						{
							$scope.transportRoutes = [];
						}
						else
						{
							$scope.transportRoutes = result.data.map(function(item){
								item.amount = parseFloat(item.amount);
								return item;
							});
						}
					}
					
				}, function(){});
			}
		}
		
		
	}
	$scope.initializeController();
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	$scope.toggleClassCat = function(item) 
	{
		var id = $scope.classCatSelection.indexOf(item);

		// is currently selected
		if (id > -1) {
			$scope.classCatSelection.splice(id, 1);
		}

		// is newly selected
		else {		
			$scope.classCatSelection.push(item);
		}

	};
	
	$scope.save = function(form)
	{

		if ( !form.$invalid ) 
		{
			var data = $scope.item;
			data.new_student_only = ( data.new_student_only ? 't' : 'f' );
			data.optional = ( data.optional ? 't' : 'f' );
			data.replaceable = ( data.replaceable ? 't' : 'f' );
			data.class_cats_restriction = $scope.classCatSelection;
			data.user_id = $rootScope.currentUser.user_id;
			
			if( $scope.edit )
			{
				if( $scope.isTransport )
				{
					var routeData = {
						user_id: $rootScope.currentUser.user_id,
						routes: $scope.transportRoutes
					}
					apiService.updateRoutes(routeData,function(response,status){
						var result = angular.fromJson( response );
						if( result.response == 'success' )
						{
							apiService.updateFeeItem(data,createCompleted,apiError);
						}
						else
						{
							$scope.error = true;
							$scope.errMsg = result.data;
						}
						
					},apiError);
				}
				else
				{
					apiService.updateFeeItem(data,createCompleted,apiError);
				}
			}
			else
			{
				apiService.addFeeItem(data,createCompleted,apiError);
			}
			
			
		}
	}
	
	var createCompleted = function ( response, status, params ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close(result.data);
			var msg = ($scope.deleted ? 'Fee Item was deleted.' : ( $scope.edit ? 'Fee Item was updated' :  'Fee Item was added.'));
			$rootScope.$emit('feeItemAdded', {'msg' : msg, 'clear' : true, 'data': result.data});
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
		var msg = ( result.data.indexOf('"U_route"') > -1 ? 'Duplicate route name.' : result.data);
		$scope.errMsg = msg;
	}
	
	$scope.deleteFeeItem = function()
	{
		$scope.error = false;
		apiService.checkFeeItem($scope.item.fee_item_id,function(response,status){
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				var canDelete = ( parseInt(result.data.num_students) == 0 ? true : false );
				
				if( canDelete )
				{
					var dlg = $dialogs.confirm('Delete Subject','Are you sure you want to permanently delete fee item <strong>' + $scope.item.fee_item + '</strong>? ',{size:'sm'});
					dlg.result.then(function(btn){
						$scope.deleted = true;
						apiService.deleteFeeItem($scope.item.fee_item_id,createCompleted,apiError);
					});
				}
				else
				{
					var dlg = $dialogs.confirm('Please Confirm','Fee Item <strong>' + $scope.item.fee_item + '</strong> is associated with <b>' + result.data.num_students + '</b> students. Are you sure you want to mark this fee item as in-active? ',{size:'sm'});
					dlg.result.then(function(btn){
						var data = {
							user_id : $rootScope.currentUser.user_id,
							fee_item_id: $scope.item.fee_item_id,
							status: 'f'
						}
						apiService.setFeeItemStatus(data,createCompleted,apiError);

					});
				}
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
		},apiError)
		
	}
	
	$scope.activateFeeItem = function()
	{
		var data = {
			user_id : $rootScope.currentUser.user_id,
			fee_item_id: $scope.item.fee_item_id,
			status: 't'
		}
		apiService.setFeeItemStatus(data,createCompleted,apiError);
	}
	
	$scope.addRoute = function()
	{		
		$scope.transportRoutes.push({
			transport_id:undefined,
			route:undefined,
			amount:undefined
		});
	}
	
	$scope.removeRoute = function(index)
	{
		$scope.transportRoutes.splice(index,1);
	}
	
	
} ]);