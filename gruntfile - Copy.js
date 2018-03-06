module.exports = function(grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		uncss: {
			dist: {
				files: {
					'src/css/template.css' : ['index.html']
				}
			}
		},
		watch: {
			sass: {
				files: 'src/sass/*.scss',
				tasks: ['sass','cssmin']
			}
		},
		sass: {
			dist: {
				files: {
					'src/css/template.css' : 'src/sass/template.scss'
				}
			}
		},		
		cssmin: {
			options: {
				shorthandCompacting: false,
				roundingPrecision: -1
			},
			my_target: {
				files: [
					{
						expand: true,
						cwd: 'src/css/',
						src: ['template.css','!*.min.css'],
						dest: 'src/css/',
						ext: '.min.css'
					},
					/*{
					  'src/min/css/dependencies.min.css': ['src/components/css/bootstrap.min.css',
												//	'src/components/css/datatables.min.css',
													'src/components/css/daterangepicker.css',
													'src/components/css/dialogs.css',
													'src/components/css/font-awesome.min.css',
													'src/components/css/select2.css',
													'src/components/css/select2-bootstrap.css',
													'src/components/css/select.min.css']
					}*/
				]
			}
		},
		 uglify: {
			options: {
			  compress: {
				drop_console: true
			  },
			  mangle: {
				mangle: false
				//except: ['jQuery']
			  },
			  ascii_only:true
			},
			my_target: {
			  files: {
				'src/min/dependencies.min.js': ['src/components/bootstrap.min.js',
												//'src/components/datatables.min.js',
												'src/components/angular-ui-router.min.js',
												'src/components/ui-bootstrap-tpls.min.js',
												'src/components/moment.min.js',
												'src/components/daterangepicker.js',
												'src/components/angular-daterangepicker.js',
												'src/components/dialogs.min.js',
												'src/components/filesaver.min.js',
												'src/components/select.min.js',
												'src/components/angular-file-upload.min.js'],
				'src/min/loadfirst.min.js': ['src/components/jquery.min.js',
												'src/components/angular.min.js',
												'src/components/angular-sanitize.min.js'],
				'src/min/app.min.js': ['src/app/app.js',
										'src/app/routing.js',
										'src/services/authInterceptor.js',
										'src/app/directive.js',
										'src/app/globalFunctions.js',
										'src/app/parentController.js',
										'src/app/landingCtrl.js',
										'src/app/dashboardCtrl.js',
										'src/app/login.js',
										'src/app/*/*.js',
										'src/services/auth.js',
										'src/services/session.js',
										'src/services/AjaxServices.js',
										'src/services/apiServices.js'
										]
			  }
			}
		  }
	});
	grunt.loadNpmTasks('grunt-uncss');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.registerTask('default',['watch']);
}