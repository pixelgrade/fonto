var gulp 		= require('gulp'),
	sass 		= require('gulp-sass'),
	prefix 		= require('gulp-autoprefixer'),
	notify 		= require('gulp-notify'),
	csscomb 	= require('gulp-csscomb'),
	rtlcss 		= require('rtlcss'),
	postcss 	= require('gulp-postcss'),
	rename 		= require('gulp-rename'),
	chmod 		= require('gulp-chmod');

require('es6-promise').polyfill();

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
			.pipe(sass({ 'sourcemap=auto': true, outputStyle: 'compressed' }))
			.pipe(prefix("last 1 version", "> 1%", "ie 8", "ie 7"))
			.pipe(csscomb())
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

gulp.task('styles-watch', function () {
	return gulp.watch('assets/scss/**/*.scss', ['styles']);
});