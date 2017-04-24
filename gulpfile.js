var plugin = 'fonto',
	gulp 		= require('gulp'),
	sass 		= require('gulp-sass'),
	prefix 		= require('gulp-autoprefixer'),
	notify 		= require('gulp-notify'),
	csscomb 	= require('gulp-csscomb'),
	rtlcss 		= require('rtlcss'),
	postcss 	= require('gulp-postcss'),
	rename 		= require('gulp-rename'),
	chmod 		= require('gulp-chmod'),
	cleanCSS = require('gulp-clean-css'),
	exec        = require('gulp-exec'),
	fs          = require('fs'),
	del         = require('del');

require('es6-promise').polyfill();

var options = {
	silent: true,
	continueOnError: true // default: false
};

/**
 *   #STYLES
 */

gulp.task('styles-expanded', function () {
	return gulp.src(['assets/css/cmb2/sass/cmb2.scss'])
			.pipe(sass({ 'sourcemap=auto': true, outputStyle: 'expanded' }))
			.pipe(prefix("last 1 version", "> 1%", "ie 8", "ie 7"))
			.pipe(csscomb())
			.pipe(chmod(644))
			.pipe(gulp.dest('./assets/css/cmb2/'))
			.pipe(postcss([
				require('rtlcss')({ /* options */ })
			]))
			.pipe(rename("cmb2-rtl.css"))
			.pipe(gulp.dest('./assets/css/cmb2/'))
});

gulp.task('styles-compressed', function () {
	return gulp.src(['assets/css/cmb2/sass/cmb2.scss'])
			.pipe(sass({ 'sourcemap=auto': true }))
			.pipe(prefix("last 1 version", "> 1%", "ie 8", "ie 7"))
			.pipe(cleanCSS())
			.pipe(chmod(644))
			.pipe(rename("cmb2.min.css"))
			.pipe(gulp.dest('./assets/css/cmb2/'))
			.pipe(postcss([
				require('rtlcss')({ /* options */ })
			]))
			.pipe(rename("cmb2-rtl.min.css"))
			.pipe(gulp.dest('./assets/css/cmb2/'))
});

gulp.task('styles', ['styles-expanded', 'styles-compressed'], function () {
	console.log('The styles and scripts have been compiled for production! Go and clear the caches!');
});

gulp.task('watch', function () {
	return gulp.watch('assets/css/cmb2/sass/**/*.scss', ['styles']);
});

/**
 * Copy theme folder outside in a build folder, recreate styles before that
 */
gulp.task( 'copy-folder', function() {

	return gulp.src('./')
		.pipe(exec('rm -Rf ./../build; mkdir -p ./../build/' + plugin + '; rsync -av --exclude="node_modules" ./* ./../build/' + plugin + '/', options));
} );

/**
 * Clean the folder of unneeded files and folders
 */
gulp.task( 'build', ['copy-folder'], function() {

	// files that should not be present in build zip
	files_to_remove = [
		'**/codekit-config.json',
		'node_modules',
		'config.rb',
		'gulpfile.js',
		'package.json',
		'pxg.json',
		'build',
		'.idea',
		'**/*.css.map',
		'**/.git*',
		'*.sublime-project',
		'.DS_Store',
		'**/.DS_Store',
		'__MACOSX',
		'**/__MACOSX',
		'+development.rb',
		'+production.rb',
		'README.md',
		'.labels'
	];

	files_to_remove.forEach(function (e, k) {
		files_to_remove[k] = '../build/' + plugin + '/' + e;
	});

	return del.sync(files_to_remove, {force: true});
} );

/**
 * Create a zip archive out of the cleaned folder and delete the folder
 */
gulp.task( 'zip', ['build'], function() {

	return gulp.src('./')
		.pipe(exec('cd ./../; rm -rf ' + plugin + '.zip; cd ./build/; zip -r -X ./../' + plugin + '.zip ./; cd ./../; rm -rf build'));

} );