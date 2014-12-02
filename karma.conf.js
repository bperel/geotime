// Karma configuration
// Generated on Sat Nov 29 2014 23:04:14 GMT+0100 (Paris, Madrid)

module.exports = function(config) {
    config.set({

        // base path that will be used to resolve all patterns (eg. files, exclude)
        basePath: '',


        // frameworks to use
        // available frameworks: https://npmjs.org/browse/keyword/karma-adapter
        frameworks: ['jasmine-jquery', 'jasmine'],


        // list of files / patterns to load in the browser
        files: [
            { pattern: 'map-placeholders.html', watched: false, included: false },
            { pattern: 'test/geotime/_data/*', watched: false, included: false },
            { pattern: 'js/vendor/modernizr/modernizr.js', watched: false },
            { pattern: 'js/vendor/d3/d3.min.js', watched: false },
            { pattern: 'js/vendor/topojson/topojson.js', watched: false },
            { pattern: 'js/vendor/jasmine/lib/jasmine-core/jasmine.js', watched: false },
            //{ pattern: 'js/vendor/jasmine-matchers/src/*.js', watched: false },
            { pattern: 'js/vendor/jquery/dist/jquery.min.js', watched: false },
            { pattern: 'js/vendor/jasmine-jquery/lib/jasmine-jquery.js', watched: false },
            { pattern: 'test/jasmine/mock-ajax.js', watched: false },
            'js/*.js',
            'test/jasmine/*.spec.js'
        ],


        // list of files to exclude
        exclude: [
        ],


        // preprocess matching files before serving them to the browser
        // available preprocessors: https://npmjs.org/browse/keyword/karma-preprocessor
        preprocessors: {
            'js/*.js': ['coverage']
        },


        // test results reporter to use
        // possible values: 'dots', 'progress'
        // available reporters: https://npmjs.org/browse/keyword/karma-reporter
        reporters: ['progress', 'junit', 'coverage'],

        junitReporter: {
	    outputFile: 'build/js/test-results.xml',
            suite: ''
        },

        coverageReporter: {
            type: 'html',
            dir: 'build/js/coverage/',
            subdir: '.'
        },

        // web server port
        port: 9876,


        // enable / disable colors in the output (reporters and logs)
        colors: true,


        // level of logging
        // possible values: config.LOG_DISABLE || config.LOG_ERROR || config.LOG_WARN || config.LOG_INFO || config.LOG_DEBUG
        logLevel: config.LOG_DEBUG,


        // enable / disable watching file and executing tests whenever any file changes
        autoWatch: true,


        // start these browsers
        // available browser launchers: https://npmjs.org/browse/keyword/karma-launcher
        browsers: ['PhantomJS'],


        // Continuous Integration mode
        // if true, Karma captures browsers, runs the tests and exits
        singleRun: true
    });
};
