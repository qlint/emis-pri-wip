angular
  .module('myApp', [])
  .controller('myCtrl', function($scope) {
    $scope.ratings = ["a1", "a2", "a3", "a4", "a5"];
    var response = {
      "list": [{
        "name": "firstRow",
        "rating": "a1"
      }, {
        "name": "secondRow",
        "rating": "a4"
      }, {
        "name": "thirdRow",
        "rating": "a3"
      }, {
        "name": "fourthRow",
        "rating": "a2"
      }]
    };
    var vm = this;
    vm.data = response.list;

    vm.myratingChanged = function(myrating) {
      $scope.$broadcast("myratingchanged", myrating)
    }

  })
  .directive('dropdown', function($timeout) {
    return {
      restrict: 'A',
      require: 'ngModel',
      scope: {
        list: '=dropdown',
        ngModel: '='
      },
      templateUrl: "dropdownView.html",
      link: function(scope, elem, attrs, ngModel) {
        scope.height = elem[0].offsetHeight;
        scope.$watch('ngModel', function() {
          scope.selected = ngModel.$modelValue;
        });

        scope.$on('myratingchanged', function(event, data) {
          scope.selected = data
        })

        scope.update = function(rating) {
          ngModel.$setViewValue(rating);
          ngModel.$render();
        };
      }
    };
  });
