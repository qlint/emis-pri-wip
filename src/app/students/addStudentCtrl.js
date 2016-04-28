'use strict';

angular.module('eduwebApp').
controller('addStudentCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'FileUploader', 'data','$parse',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, FileUploader, data, $parse){
	
	$scope.tabs = ['Student Details','Parents','Medical History','Fee Items'];
	$scope.formNames = ['studentDetails','parentGuardians','medicalHistory','feeItems'];
	$scope.currentTab = 'Student Details';
	$scope.currentStep = 0;
	$scope.firstStep = true;
	$scope.forms = {};
	
	$scope.student = {};
	var start_date = moment().format('YYYY-MM-DD HH:MM');
	$scope.student.admission_date = {startDate: start_date};
	$scope.student.student_category = 'Regular';
	$scope.student.nationality = 'Kenya';
	$scope.student.status = 'true';
	$scope.student.other_medical_conditions = 'false';
	$scope.student.hospitalized = 'false';
	$scope.student.current_medical_treatment = 'false';
	$scope.showSeparationAge = false;	
	
	$scope.feeItemSelection = [];
	$scope.optFeeItemSelection = [];
	$scope.conditionSelection = [];
	$scope.formError = false;
	
	var detailsSection = ['new_student', 'admission_number', 'current_class', 'last_name', 'first_name', 'dob', 'gender', 'emergency_name', 'emergency_relationship', 'emergency_telephone'];
	var guardianSection = ['father_last_name','father_first_name','father_id_number','father_telephone','father_email', 'mother_last_name','mother_first_name','mother_id_number','mother_telephone','mother_email'];
	var feesSection = ['payment_method','installment_option'];
	$scope.submitted = false;
	
	$scope.initializeController = function()
	{
		var studentCats = $rootScope.currentUser.settings['Student Categories'];
		$scope.studentCats = studentCats.split(',');	
		
		var paymentOptions = $rootScope.currentUser.settings['Payment Options'];
		$scope.paymentOptions = paymentOptions.split(',');	
		
		var relationships = $rootScope.currentUser.settings['Guardian Relationships'];
		$scope.relationships = relationships.split(',');	
		
		var maritalStatuses = $rootScope.currentUser.settings['Marital Statuses'];
		$scope.maritalStatuses = maritalStatuses.split(',');
		
		var titles = $rootScope.currentUser.settings['Titles'];
		$scope.titles = titles.split(',');
		
		var medicalConditions = $rootScope.currentUser.settings['Medical Conditions'];
		medicalConditions = medicalConditions.split(',');
		
		// map medicalConditions to an object to hold user entry fields
		$scope.medicalConditions = medicalConditions.reduce(function(sum,item){
			sum.push({
				'medical_condition' : item,
				'age': '',
				'comments' :''
			});
			return sum;
		}, []);
		
		
		// get fee items
		apiService.getFeeItems({}, function(response){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{
				$scope.feeItems = formatFeeItems(result.data.required_items);
				$scope.allFeeItems = $scope.feeItems;
				
				$scope.optFeeItems = formatFeeItems(result.data.optional_items);
				$scope.allOptFeeItems = $scope.optFeeItems;
			}
			
		}, function(){});
		
		// get transport routes
		apiService.getTansportRoutes({}, function(response){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{
				$scope.transportRoutes = result.data;
			}
			
		}, function(){});
		
		
	}
	$scope.initializeController();
	
	var formatFeeItems = function(feeItems)
	{
		// convert the classCatsRestriction to array for future filtering
		return feeItems.map(function(item){
			// format the class restrictions into any array
			if( item.class_cats_restriction !== null && item.class_cats_restriction != '{}' )
			{
				var classCatsRestriction = (item.class_cats_restriction).slice(1, -1);
				item.class_cats_restriction = classCatsRestriction.split(',');
			}
			item.amount = undefined;
			item.payment_method = undefined;
			
			return item;
		});
	}
	
	$scope.getStep = function(direction, theForm)
	{
		// validate current form first
		//var theForm = $parse('forms.' + $scope.currentForm)($scope);
		console.log(theForm);
		if( theForm.$pristine )
		{
			goTo(direction, theForm); 
		}
		else
		{
			theForm.$setDirty();
			theForm.$setSubmitted();
			if( !theForm.$invalid ) goTo(direction, theForm); 
		}
		
	}
	
	var goTo = function(direction, theForm)
	{
		$scope.currentStep = (direction == 'next' ? ($scope.currentStep+1): ($scope.currentStep-1));
		$scope.getTabContent($scope.tabs[$scope.currentStep],theForm);
	}

	$scope.getTabContent = function(tab, theForm)
	{
		// store the form for future use
		$scope.forms[$scope.currentTab] = angular.copy(theForm);
		if( $scope.submitted  ) countErrors();
		
		$scope.currentTab = tab;
		$scope.currentStep = $scope.tabs.indexOf(tab);
		$scope.currentForm = $scope.formNames[$scope.currentStep]; 
		$scope.lastStep = false;
		$scope.firstStep = false;
		if( $scope.currentTab == $scope.tabs[0] ) $scope.firstStep = true;
		else if( $scope.currentTab == $scope.tabs[ $scope.tabs.length - 1 ] ) $scope.lastStep = true;;	
	}
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	$scope.$watch('student.payment_method', function(newVal, oldVal){
		if( newVal == oldVal) return;
		
		// want to set all selected fee item payment methods to this value
		
		
	});
	
	$scope.$watch('student.new_student', function(newVal, oldVal){
		if( newVal == oldVal ) return;
		$scope.feeItems = filterFeeItems($scope.allFeeItems);		
	});
	
	$scope.$watch('student.current_class', function(newVal, oldVal){
		if( newVal == oldVal) return;
		$scope.feeItems = filterFeeItems($scope.allFeeItems);
		$scope.optFeeItems = filterFeeItems($scope.allOptFeeItems);			
	});
	
	$scope.$watch('student.transport_route', function(newVal, oldVal){
		if( newVal == oldVal) return;
		console.log(newVal);
		// use the amount and put it into the input box
		angular.forEach($scope.optFeeItemSelection, function(feeItem,key){
			if( feeItem.fee_item == 'Transport') feeItem.amount = newVal.amount;
		});
	});
	
	var filterFeeItems = function(feesArray)
	{
		var feeItems = [];

		if( $scope.student.new_student == 'true' )
		{
			// all fees apply to new students
			feeItems = feesArray;
		}
		else
		{
			// remove new student fees
			feeItems = feesArray.filter(function(item){
				if( !item.new_student_only ) return item;
			});
		}
		
		// now filter by selected class
		if( $scope.student.current_class !== undefined )
		{
			feeItems = feeItems.filter(function(item){
				if( item.class_cats_restriction === null || item.class_cats_restriction == '{}' ) return item;
				else if( item.class_cats_restriction.indexOf(($scope.student.current_class.class_cat_id).toString()) > -1 ) return item;
			});
		}

		return feeItems;
	}

	$scope.toggleFeeItem = function(item,type) 
	{
		var selectionObj = ( type ==  'optional'  ? $scope.optFeeItemSelection : $scope.feeItemSelection);
		var id = selectionObj.indexOf(item);

		// is currently selected
		if (id > -1) {
			selectionObj.splice(id, 1);
			
			// clear out fields
			item.amount = undefined;
			item.payment_method = undefined;
			if( item.fee_item == 'Transport' ) $scope.showTransport = false;
		}

		// is newly selected
		else {
		
			// set value and payment method
			item.amount = item.default_amount;
			item.payment_method = $scope.student.payment_method;
			
			selectionObj.push(item);
			if( item.fee_item == 'Transport' ) $scope.showTransport = true;
			
		}
	};
	
	$scope.toggleMedicalCondition = function(item) 
	{
	
		var id = $scope.conditionSelection.indexOf(item);

		// is currently selected
		if (id > -1) {
			$scope.conditionSelection.splice(id, 1);
		}

		// is newly selected
		else {
			$scope.conditionSelection.push(item);
		}
	};
	
	$scope.hasErrors = function(tab)
	{
		switch(tab){
			case 'Student Details':
				return ($scope.detailsErrors > 0 ? true : false)
				break;
			case 'Parents':
				return ($scope.guardianErrors > 0 ? true : false)
				break;
			case 'Medical History':
				return ($scope.medicalErrors > 0 ? true : false)
				break;
			case 'Fee Items':
				return ($scope.feeErrors > 0 ? true : false)
				break;
		}
	}
	
	$scope.getErrorCount = function(tab)
	{
		switch(tab){
			case 'Student Details':
				return $scope.detailsErrors;
				break;
			case 'Parents':
				return $scope.guardianErrors;
				break;
			case 'Medical History':
				return $scope.medicalErrors;
				break;
			case 'Fee Items':
				return $scope.feeErrors;
				break;
		}
	}
	
	$scope.save = function(theForm)
	{	
		$scope.forms[$scope.currentTab] = angular.copy(theForm);
		$scope.submitted = true;
		
		if( !theForm.$invalid)
		{
			if( uploader.queue[0] !== undefined ){
				$scope.student.student_image = uploader.queue[0].file.name;
			}
			
			$scope.student.has_medical_conditions = ( $scope.conditionSelection.length > 0 || $scope.student.other_medical_conditions ? true : false );

			var postData = angular.copy($scope.student);
			postData.admission_date = $scope.student.admission_date.startDate;
			postData.current_class = $scope.student.current_class.class_id;		
			postData.medicalConditions = $scope.conditionSelection;
			postData.feeItems = $scope.feeItemSelection;
			postData.optFeeItems = $scope.optFeeItemSelection;
			postData.user_id = $rootScope.currentUser.user_id;
			console.log(postData);
			
			apiService.postStudent(postData, createCompleted, createError);
		}
		else
		{
			$scope.formError = true;
			$scope.errMsg = "There were errors found in the form.";
			console.log(theForm);			
			countErrors();
		}
	}
	
	
	var countErrors = function()
	{		
		$scope.detailsErrors = 0;
		$scope.guardianErrors = 0;
		$scope.feeErrors = 0;
		$scope.formErrorList = [];
		console.log($scope.forms);
		angular.forEach( $scope.forms, function(myForm, tab){
			
			 for (var key in myForm.$error) 
			 {
				for (var index = 0; index < myForm.$error[key].length; index++) 
				{
					$scope.formErrorList.push(myForm.$error[key][index].$name + ' is required.');

					if( detailsSection.indexOf(myForm.$error[key][index].$name) > -1 ) $scope.detailsErrors++;
					else if( guardianSection.indexOf(myForm.$error[key][index].$name) > -1 ) $scope.guardianErrors++;
					else if( feesSection.indexOf(myForm.$error[key][index].$name) > -1 ) $scope.feeErrors++;
				}
			}						
		});
	}
	
	var createCompleted = function ( response, status ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			uploader.uploadAll();
			$uibModalInstance.close();
			$rootScope.$emit('studentAdded', {'msg' : 'Student was created.', 'clear' : true});
		}
		else
		{
			$scope.formError = true;
			$scope.errMsg = result.data;
			//$rootScope.$emit('jobError', {'msg' : result.data });
		}
	}
	
	var createError = function () 
	{
		var result = angular.fromJson( response );
		$scope.formError = true;
		$scope.errMsg = result.data;
	}
	
	var uploader = $scope.uploader = new FileUploader({
            url: 'upload.php',
			formData : [{
				'dir': 'students'
			}]
    });
	
} ]);