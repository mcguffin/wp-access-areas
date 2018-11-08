var autoprefixer = require('gulp-autoprefixer');
var concat = require('gulp-concat');
var fs = require('fs');
var gulp = require('gulp');
var gulputil = require('gulp-util');
var rename = require('gulp-rename');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var uglify = require('gulp-uglify');

function _src_dest_js( src ) {
	var ret = {
		src:null,
		dest: false
	}

	if ( src.constructor === Array ) {
		ret.src = [];
		for ( var i=0;i<src.length;i++) {
			if ( ret.dest === false ) {
				ret.dest = './js/' + src[i].substring( 0, src[i].lastIndexOf('/') );
			}
			ret.src.push( './src/js/' + src[i] + '.js' );
		}
	} else if ( 'string' === typeof src ) {
		ret.src = './src/js/' + src + '.js',
		ret.dest = './js/' + src.substring( 0, src.lastIndexOf('/') )
	}
	return ret;
}

function do_scss( src ) {
	var dir = src.substring( 0, src.lastIndexOf('/') );
	return gulp.src( './src/scss/' + src + '.scss' )
		.pipe( sourcemaps.init() )
		.pipe( sass( { outputStyle: 'nested' } ).on('error', sass.logError) )
		.pipe( autoprefixer({
			browsers:['last 2 versions']
		}) )
		.pipe( gulp.dest( './css/' + dir ) )
        .pipe( sass( { outputStyle: 'compressed' } ).on('error', sass.logError) )
		.pipe( rename( { suffix: '.min' } ) )
        .pipe( sourcemaps.write() )
        .pipe( gulp.dest( './css/' + dir ) );

}

function do_js( src ) {
	var s = _src_dest_js(src);

	return gulp.src( s.src )
		.pipe( sourcemaps.init() )
		.pipe( gulp.dest( s.dest ) )
		.pipe( uglify().on('error', gulputil.log ) )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( sourcemaps.write() )
		.pipe( gulp.dest( s.dest ) );
}

function concat_js( src, dest_name ) {
	var s = _src_dest_js(src);

	return gulp.src( s.src )
		.pipe( sourcemaps.init() )
		.pipe( concat( dest_name ) )
		.pipe( sourcemaps.write() )
		.pipe( gulp.dest( s.dest ) )
		.pipe( uglify().on('error', gulputil.log ) )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( sourcemaps.write() )
		.pipe( gulp.dest( s.dest ) );

}


gulp.task('dashicons',function(){
	var codepoints = require('./src/scss/variables/dashicons-codepoints.json'),
		line, contents = '';
	for (var c in codepoints ) {
		line = '$dashicon-' + c + ': "\\' + codepoints[c].toString(16) + '";\n\n';
		contents += line;
	}
	fs.writeFileSync( './src/scss/variables/_dashicons.scss', contents );
});


gulp.task('scss:admin:aa',function(){
	return do_scss('admin/access-areas');
});
gulp.task('scss:admin:posts',function(){
	return do_scss('admin/posts');
});
gulp.task('scss:admin:settings',function(){
	return do_scss('admin/settings');
});
gulp.task('scss', gulp.parallel('scss:admin:aa','scss:admin:posts','scss:admin:settings'));



gulp.task('js:admin:aa',function(){
	return concat_js( [
		'admin/base',
		'admin/manage',
		'admin/users',
	], 'access-areas.js');
});
gulp.task('js:admin:settings',function(){
	return do_js('admin/settings');
})


gulp.task( 'js', gulp.parallel('js:admin:settings','js:admin:aa') );


gulp.task('build', gulp.parallel('scss','js') );

gulp.task('watch', function() {
	// place code for your default task here
	gulp.watch('./src/scss/**/*.scss',gulp.parallel( 'scss' ));
	gulp.watch('./src/js/**/*.js',gulp.parallel( 'js' ) );
});
gulp.task('default', gulp.parallel('build','watch'));
