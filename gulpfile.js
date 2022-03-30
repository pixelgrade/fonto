var plugin = 'fonto',
	gulp 		= require('gulp'),
  plugins = require('gulp-load-plugins')(),
  fs = require('fs'),
  cp = require('child_process'),
  del = require('del'),
  cleanCSS = require('gulp-clean-css'),
  commandExistsSync = require('command-exists').sync;

require('es6-promise').polyfill();
var log = require('fancy-log');
/**
 *   #STYLES
 */

gulp.task('styles-expanded', function () {
	return gulp.src(['assets/css/cmb2/sass/cmb2.scss'])
			.pipe(plugins.sass({ 'sourcemap=auto': true, outputStyle: 'expanded' }))
			.pipe(plugins.autoprefixer())
			.pipe(plugins.csscomb())
			.pipe(gulp.dest('./assets/css/cmb2/', {"mode": "0644"}))
			.pipe(plugins.postcss([
				require('rtlcss')({ /* options */ })
			]))
			.pipe(plugins.rename("cmb2-rtl.css"))
			.pipe(gulp.dest('./assets/css/cmb2/'))
});

gulp.task('styles-compressed', function () {
	return gulp.src(['assets/css/cmb2/sass/cmb2.scss'])
			.pipe(plugins.sass({ 'sourcemap=auto': true }))
			.pipe(plugins.autoprefixer())
			.pipe(cleanCSS())
			.pipe(plugins.rename("cmb2.min.css"))
			.pipe(gulp.dest('./assets/css/cmb2/', {"mode": "0644"}))
			.pipe(plugins.postcss([
				require('rtlcss')({ /* options */ })
			]))
			.pipe(plugins.rename("cmb2-rtl.min.css"))
			.pipe(gulp.dest('./assets/css/cmb2/'))
});

function stylesSequence(cb) {
  return gulp.series( 'styles-expanded', 'styles-compressed' )(cb);
}
stylesSequence.description = 'The styles and scripts have been compiled for production! Go and clear the caches!';
gulp.task('styles', stylesSequence);

gulp.task('watch', function () {
	return gulp.watch('assets/css/cmb2/sass/**/*.scss', ['styles']);
});

// ---------
// SCRIPTS
// ---------

// Create minified versions of scripts.
function minifyScripts() {
  return gulp.src(['./assets/js/*.js', '!./assets/js/*.min.js'],{base: './assets/js/'})
    .pipe( plugins.terser({
      warnings: true,
      compress: true, mangle: true,
      output: { comments: 'some' }
    }))
    .pipe(plugins.rename({
      suffix: ".min"
    }))
    .pipe(gulp.dest('./assets/js'));
}
gulp.task( 'minify-scripts', minifyScripts );

const wpPot = require('gulp-wp-pot');
const sort = require('gulp-sort');
const notify = require('gulp-notify');

// -----------------------------------------------------------------------------
// Generate POT file from the build folder.
// -----------------------------------------------------------------------------
function translate() {
  return gulp
    .src('./**/*.php')
    .pipe(sort())
    .pipe(
      wpPot({
        domain: 'fonto',
        package: 'fonto',
        headers: false
      })
    )
    .pipe(gulp.dest('./languages/fonto.pot'))
    .pipe(
      notify({
        message: '\n\n✅ build:translate — completed!\n',
        onLast: true
      })
    );
}

translate.description = 'Generate POT File and move it to languages folder';
gulp.task( 'build:translate', translate );

// -----------------------------------------------------------------------------
// Copy plugin folder outside in a build folder
// -----------------------------------------------------------------------------
function copyFolder() {
  var dir = process.cwd();
  return gulp.src( './*' )
    .pipe( plugins.exec( 'rm -Rf ./../build; mkdir -p ./../build/' + plugin + ';', {
      silent: true,
      continueOnError: true // default: false
    } ) )
    .pipe(plugins.rsync({
      root: dir,
      destination: '../build/' + plugin + '/',
      // archive: true,
      progress: false,
      silent: true,
      compress: false,
      recursive: true,
      emptyDirectories: true,
      clean: true,
      exclude: ['node_modules']
    }));
}
copyFolder.description = 'Copy plugin production files to a build folder';
gulp.task( 'copy-folder', copyFolder );


// -----------------------------------------------------------------------------
// Remove unneeded files and folders from the build folder
// -----------------------------------------------------------------------------
function removeUnneededFiles() {
  // Files that should not be present in build
  files_to_remove = [
    '**/codekit-config.json',
    'node_modules',
    'config.rb',
    'gulp-tasks',
    'gulpfile.js',
    'webpack.common.js',
    'webpack.dev.js',
    'webpack.prod.js',
    'css',
    '.idea',
    '.editorconfig',
    '**/.svn*',
    '**/*.css.map',
    '**/.sass*',
    '.sass*',
    '**/.git*',
    '*.sublime-project',
    '.DS_Store',
    '**/.DS_Store',
    '__MACOSX',
    '**/__MACOSX',
    'README.md',
    '**/README.md',
    'CONTRIBUTING.md',
    '.csscomb',
    '.csscomb.json',
    '.codeclimate.yml',
    'tests',
    'circle.yml',
    '.circleci',
    '.labels',
    '.jscsrc',
    '.jshintignore',
    'browserslist',
    '.stylelintrc',
    'tsconfig.json',
    'tslint.json',
    'webpack.config.js',
    '.jscsrc',
    '.jshintignore',
    'phpcs.xml.dist',
    'phpunit.xml.dist',
    'bundlesize.config.json',
    'postcss.config.js',

    '**/package.json',
    '**/package-lock.json',

    'bin',
    'babel.config.js',
    '.nvmrc',

    'assets/css/cmb2/sass',
    'includes/vendor/CMB2/css'
  ];

  files_to_remove.forEach( function( e, k ) {
    files_to_remove[k] = '../build/' + plugin + '/' + e;
  } );

  return del( files_to_remove, {force: true} );
}
removeUnneededFiles.description = 'Remove unneeded files and folders from the build folder';
gulp.task( 'remove-unneeded-files', removeUnneededFiles );

function removeEmptyFolders(done) {
  function cleanEmptyFoldersRecursively(folder) {
    var fs = require('fs');
    var path = require('path');

    var isDir = fs.statSync(folder).isDirectory();
    if (!isDir) {
      return;
    }
    var files = fs.readdirSync(folder);
    if (files.length > 0) {
      files.forEach(function(file) {
        var fullPath = path.join(folder, file);
        cleanEmptyFoldersRecursively(fullPath);
      });

      // re-evaluate files; after deleting subfolder
      // we may have parent folder empty now
      files = fs.readdirSync(folder);
    }

    if (files.length == 0) {
      console.log("removing: ", folder);
      fs.rmdirSync(folder);
      return;
    }
  }
  cleanEmptyFoldersRecursively('./../build/' + plugin + '/');

  return done();
}
removeEmptyFolders.description = 'Remove empty folders from the build folder';
gulp.task( 'remove-empty-folders', removeEmptyFolders );

function maybeFixBuildDirPermissions(done) {

  cp.execSync('find ./../build -type d -exec chmod 755 {} \\;');

  return done();
}
maybeFixBuildDirPermissions.description = 'Make sure that all directories in the build directory have 755 permissions.';
gulp.task( 'fix-build-dir-permissions', maybeFixBuildDirPermissions );

function maybeFixBuildFilePermissions(done) {

  cp.execSync('find ./../build -type f -exec chmod 644 {} \\;');

  return done();
}
maybeFixBuildFilePermissions.description = 'Make sure that all files in the build directory have 644 permissions.';
gulp.task( 'fix-build-file-permissions', maybeFixBuildFilePermissions );

function maybeFixIncorrectLineEndings(done) {
  if (!commandExistsSync('dos2unix')) {
    log.warn( 'Could not ensure that line endings are correct on the build files since you are missing the "dos2unix" utility! You should install it.' );
    log.warn( 'However, this is not a very big deal. The build task will continue.' );
  } else {
    cp.execSync('find ./../build -type f -print0 | xargs -0 -n 1 -P 4 dos2unix');
  }

  return done();
}
maybeFixIncorrectLineEndings.description = 'Make sure that all line endings in the files in the build directory are UNIX line endings.';
gulp.task( 'fix-line-endings', maybeFixIncorrectLineEndings );

// -----------------------------------------------------------------------------
// Replace the plugin's text domain with the actual text domain
// -----------------------------------------------------------------------------
function pluginTextdomainReplace() {
  return gulp.src( ['../build/' + plugin + '/**/*.php', '../build/' + plugin + '/**/*.js', '../build/' + plugin + '/**/*.pot'] )
    .pipe( plugins.replace( /__plugin_txtd/g, plugin ) )
    .pipe( gulp.dest( '../build/' + plugin ) );
}
gulp.task( 'txtdomain-replace', pluginTextdomainReplace );

function buildSequence(cb) {
  return gulp.series( 'build:translate', 'copy-folder', 'remove-unneeded-files', 'remove-empty-folders', 'fix-build-dir-permissions', 'fix-build-file-permissions', 'fix-line-endings', 'txtdomain-replace' )(cb);
}
buildSequence.description = 'Sets up the build folder';
gulp.task( 'build', buildSequence );


// -----------------------------------------------------------------------------
// Create the plugin installer archive and delete the build folder
// -----------------------------------------------------------------------------
function makeZip() {
  var versionString = '';
  // get plugin version from the main plugin file
  var contents = fs.readFileSync("./" + plugin + ".php", "utf8");

  // split it by lines
  var lines = contents.split(/[\r\n]/);

  function checkIfVersionLine(value, index, ar) {
    var myRegEx = /^[\s\*]*[Vv]ersion:/;
    if (myRegEx.test(value)) {
      return true;
    }
    return false;
  }

  // apply the filter
  var versionLine = lines.filter(checkIfVersionLine);

  versionString = versionLine[0].replace(/^[\s\*]*[Vv]ersion:/, '').trim();
  versionString = '-' + versionString.replace(/\./g, '-');

  return gulp.src('./')
    .pipe( plugins.exec('cd ./../; rm -rf ' + plugin[0].toUpperCase() + plugin.slice(1) + '*.zip; cd ./build/; zip -r -X ./../' + plugin[0].toUpperCase() + plugin.slice(1) + versionString + '.zip ./; cd ./../; rm -rf build'));
}
makeZip.description = 'Create the plugin installer archive and delete the build folder';
gulp.task( 'make-zip', makeZip );

function zipSequence(cb) {
  return gulp.series( 'build', 'make-zip' )(cb);
}
zipSequence.description = 'Creates the zip file';
gulp.task( 'zip', zipSequence );
