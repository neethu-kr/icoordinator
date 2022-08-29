var childProcess = require('child_process');
var execSync = childProcess.execSync;
var spawn = childProcess.spawn;
var util = require('util');

module.exports = function (grunt) {
  'use strict';

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    jslint: {
      all: {
        src: [
          'src/main/**/*.js',
          'src/test/**/*.js',
          'Gruntfile.js'
        ],
        directives: grunt.file.readJSON('.jslintrc')
      }
    },
    nodeunit: {
      all: ['src/test/js/**/*.js']
    }
  });

  grunt.registerTask('default', ['lint', 'test']);

  grunt.registerTask('run', 'Run application.', function () {
    var
      done = this.async(),
      child = spawn('node', [grunt.config.get('pkg').main]);

    child.stdout.on('data', function (data) {
      process.stdout.write(data.toString());
    });
    child.stderr.on('data', function (data) {
      process.stderr.write(data.toString());
    });
    child.on('close', function (code) {
      console.log('\nChild process exited with code: ' + code);
      done();
    });
  });

  grunt.registerTask('test', 'Testing.', ['nodeunit']);

  grunt.registerTask('lint', 'Code linting.', ['jslint']);

  grunt.loadNpmTasks('grunt-contrib-nodeunit');
  grunt.loadNpmTasks('grunt-jslint');
};
