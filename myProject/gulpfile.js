'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');

sass.compiler = require('node-sass');

const scssPath = './files/themes/overrides/scss';
const cssPath = './files/themes/overrides/css';
gulp.task('sass', function () {
  return gulp.src(`${scssPath}/**/*.scss`)
    .pipe(sass({
      outputStyle: 'compressed'
    }).on('error', sass.logError))
    .pipe(gulp.dest(cssPath));
});

gulp.task('sass:watch', function () {
  return gulp.watch(`${scssPath}/**/*.scss`, gulp.series('sass'));
});