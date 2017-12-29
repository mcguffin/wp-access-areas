var autoprefixer = require('gulp-autoprefixer');
var concat = require('gulp-concat');
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


gulp.task('scss', function() {
	return [
		do_scss('admin/access-areas'),
	];
});


gulp.task('js-admin', function() {
    return [
		concat_js( [
			'admin/base',
			'admin/manage',
			'admin/users',
		], 'access-areas.js'),
		// do_js('admin/admin'),
		// do_js('admin/users'),
		// do_js('admin/access-areas'),
    ];

});


gulp.task( 'js', function(){
	// return concat_js( [
	// ], 'access-areas.js');
} );


gulp.task('build', ['scss','js','js-admin'] );


gulp.task('watch', function() {
	// place code for your default task here
	gulp.watch('./src/scss/**/*.scss',[ 'scss' ]);
	gulp.watch('./src/js/**/*.js',[ 'js', 'js-admin' ]);
});
gulp.task('default', ['build','watch']);
