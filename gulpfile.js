var gulp = require('gulp');
var minify = require('gulp-minify');
var minify = require('gulp-minifier');

/*gulp.task('compress', function() {
  gulp.src('wp-content/themes/twentyseventeen/assets/js/custom.js')
    .pipe(minify({
        ext:{
            src:'.min.js',
            min:'.js'
        }
    }))
    .pipe(gulp.dest('dist'))
});*/

gulp.task('compress', function() {

  gulp.src('wp-content/themes/twentyseventeen/assets/css/custom.css').pipe(minify({
    minify: true,
    collapseWhitespace: true,
    conservativeCollapse: true,
    minifyJS: true,
    minifyCSS: true,
    getKeptComment: function (content, filePath) {
        var m = content.match(/\/\*![\s\S]*?\*\//img);
        return m && m.join('\n') + '\n' || '';
    }
  })).pipe(gulp.dest('wp-content/themes/twentyseventeen/assets/css/min'));

  gulp.src('wp-content/themes/twentyseventeen/assets/js/custom.js').pipe(minify({
    minify: true,
    collapseWhitespace: true,
    conservativeCollapse: true,
    minifyJS: true,
    minifyCSS: true,
    getKeptComment: function (content, filePath) {
        var m = content.match(/\/\*![\s\S]*?\*\//img);
        return m && m.join('\n') + '\n' || '';
    }
  })).pipe(gulp.dest('wp-content/themes/twentyseventeen/assets/js/min'));
});
