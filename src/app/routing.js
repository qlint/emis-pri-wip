angular.module('eduwebApp').config(['$stateProvider', '$urlRouterProvider', 'USER_ROLES',
function($stateProvider, $urlRouterProvider, USER_ROLES) {

	var _isNotMobile = (function() {
        var check = false;
        (function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4)))check = true})(navigator.userAgent||navigator.vendor||window.opera);
        return !check;
    })();


	var _isSmallScreen = (function() {
		return (window.innerWidth <= 768 ? true : false );
	})();

  // For any unmatched url, redirect to /
  $urlRouterProvider.otherwise("/");

  // Now set up the states
  $stateProvider
  	.state('index', {
      url: "/",
      templateUrl: "app/landing.html",
      data: {
          authorizedRoles: [USER_ROLES.admin, USER_ROLES.parent, USER_ROLES.staff, USER_ROLES.teacher, USER_ROLES.sys_admin]
      }
    })
	.state('login', {
      url: "/login",
      templateUrl: "app/login.html",
      data: {
          authorizedRoles: [USER_ROLES.admin, USER_ROLES.parent, USER_ROLES.staff, USER_ROLES.teacher, USER_ROLES.sys_admin]
      }
    })
	.state('dashboard', {
      url: "/dashboard",
	  templateUrl: 'app/dashboard.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.staff, USER_ROLES.teacher, USER_ROLES.sys_admin]
      }
    })
	.state('students', {
      url: "/students",
	  params: {
		class_cat_id: null,
		class_id:  null
	  },
	  templateUrl: 'app/students/listStudents.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.staff, USER_ROLES.teacher, USER_ROLES.sys_admin]
      }
    })
	.state('staff', {
      url: "/staff",
	  params: {
		category: null,
		dept:  null
	  },
	  templateUrl: 'app/staff/listStaff.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.staff, USER_ROLES.teacher, USER_ROLES.sys_admin]
      }
    })
	.state('fees/dashboard', {
      url: "/fees/dashboard",
	  templateUrl: 'app/fees/feesDashboard.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin]
      }
    })
	.state('fees/opening_balances', {
      url: "/fees/opening_balances",
	  templateUrl: 'app/fees/openingBalances.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin]
      }
    })
	.state('fees/invoices', {
      url: "/fees/invoices/:balance_status",
	  templateUrl: 'app/fees/invoices.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin]
      }
    })
	.state('fees/payments_received', {
      url: "/fees/payments_received",
	  templateUrl: 'app/fees/paymentsReceived.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin]
      }
    })
	.state('fees/fee_structure', {
      url: "/fees/fee_structure",
	  templateUrl: 'app/fees/feeStructure.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin]
      }
    })
	.state('fees/receipt/print', {
      url: "/fees/receipt/print",
	  templateUrl: 'app/fees/receipt.html',
	  controller: 'printReceiptCtrl',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin]
      }
    })
	.state('fees/invoice/print', {
      url: "/fees/invoice/print",
	  templateUrl: 'app/fees/invoice.html',
	  controller: 'printInvoiceCtrl',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin]
      }
    })
	.state('fees/statement/print', {
      url: "/fees/statement/print",
	  templateUrl: 'app/fees/statement.html',
	  controller: 'printStatementCtrl',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin]
      }
    })
	.state('school/departments', {
      url: "/school/departments",
	  templateUrl: 'app/school/departments.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin]
      }
    })
	.state('school/classes', {
      url: "/school/classes",
	  params: {
		class_cat_id: null,
		class_id: null,
	  },
	  templateUrl: 'app/school/classes.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin]
      }
    })
	.state('school/subjects', {
      url: "/school/subjects",
	  templateUrl: 'app/school/subjects.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin]
      }
    })
	.state('my_classes/subjects', {
      url: "/school/subjects",
	  templateUrl: 'app/school/subjects.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin, USER_ROLES.teacher]
      }
    })
	.state('school/grading', {
      url: "/school/grading",
	  templateUrl: 'app/school/grading.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin]
      }
    })
	.state('school/school_dates', {
      url: "/school/school_dates",
	  templateUrl: 'app/school/dates.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin]
      }
    })
	.state('school/school_settings', {
      url: "/school/school_settings",
	  templateUrl: 'app/school/settings.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin]
      }
    })
	.state('exams/exams', {
      url: "/exams",
	  templateUrl: 'app/exams/listExams.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin, USER_ROLES.teacher]
      }
    })
	.state('exams/exam_types', {
      url: "/exams/exam_types",
	  templateUrl: 'app/exams/examTypes.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin, USER_ROLES.teacher]
      }
    })
	.state('exams/report_cards', {
      url: "/exams/report_cards",
	  templateUrl: 'app/exams/listReportCards.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin, USER_ROLES.teacher]
      }
    })
	.state('exams/report_card/print', {
      url: "/exams/report_card/print",
	  templateUrl: 'app/exams/reportCard.html',
	  controller: 'printReportCardCtrl',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin, USER_ROLES.teacher]
      }
    })
	.state('exams/class_analysis', {
      url: "/exams/class_analysis",
	  templateUrl: 'app/exams/classAnalysisReport.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin, USER_ROLES.teacher]
      }
    })
		.state('exams/stream_analysis', {
	      url: "/exams/stream_analysis",
		  templateUrl: 'app/exams/streamAnalysisReport.html',
	      data: {
	         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin, USER_ROLES.teacher]
	      }
	    })
	.state('exams/analysis/print', {
      url: "/exams/analysis/print",
	  templateUrl: 'app/exams/printClassAnalysis.html',
	  controller: 'printClassAnalysisCtrl',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin, USER_ROLES.teacher]
      }
    })
		// .state('exams/analysis/print', {
	  //     url: "/exams/analysis/print",
		//   templateUrl: 'app/exams/printStreamAnalysis.html',
		//   controller: 'printStreamAnalysisCtrl',
	  //     data: {
	  //        authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin, USER_ROLES.teacher]
	  //     }
	  //   })
	.state('school_settings', {
      url: "/school_settings",
	  templateUrl: 'app/school/settings.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin]
      }
    })
	.state('users', {
      url: "/users",
	  templateUrl: 'app/users/listUsers.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.sys_admin]
      }
    })
	.state('communications/blog_posts', {
      url: "/communications/blog/posts",
	  params: {
		class_id: null,
	  },
	  templateUrl: 'app/communications/listPosts.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.teacher, USER_ROLES.sys_admin]
      }
    })
	.state('communications/homework', {
      url: "/communications/blog/homework",
	  params: {
		class_id: null,
		subject_id: null,
	  },
	  templateUrl: 'app/communications/listHomework.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.teacher, USER_ROLES.sys_admin]
      }
    })
	.state('communications/send_email', {
      url: "/communications/send_email",
	  params: {
		class_id: null,
		subject_id: null,
	  },
	  templateUrl: 'app/communications/listEmails.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.teacher, USER_ROLES.sys_admin]
      }
    })
	.state('communications/add_post', {
      url: "/communications/blog/post/:post_type",
	  params: {
		action: 'add',
		class_id: null,
		subject_id: null,
		class_subject_id: null
	  },
	  templateUrl: 'app/communications/postForm.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.teacher, USER_ROLES.sys_admin]
      }
    })
	.state('communications/edit_post', {
      url: "/communications/blog/post/:post_type/:post_id",
	  params: {
		action: 'edit',
		post: null,
	  },
	  templateUrl: 'app/communications/postForm.html',
      data: {
         authorizedRoles: [USER_ROLES.admin, USER_ROLES.teacher, USER_ROLES.sys_admin]
      }
    })

	;
}]);
