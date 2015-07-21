module.exports = function(config) {
    config.set({

        // base path that will be used to resolve all patterns (eg. files, exclude)
        basePath: '',


        // frameworks to use
        // available frameworks: https://npmjs.org/browse/keyword/karma-adapter
        frameworks: ['jasmine', 'jasmine-matchers'],


        // list of files / patterns to load in the browser
        files: [
            { pattern: 'test/**/_data/*.json', included: false },
            { pattern: 'map-placeholders.html', included: false },
            'js/vendor/modernizr/modernizr.js',
            'js/vendor/d3/d3.min.js',
            'js/vendor/topojson/topojson.js',
            'js/vendor/jquery/dist/jquery.min.js',
            'node_modules/jasmine-jquery/lib/jasmine-jquery.js',
            'js/*.js',
            'test/jasmine/mock-ajax.js',
            'test/jasmine/*.spec.js'
        ],


        // list of files to exclude
        exclude: [
            'js/main.js',
            'js/main_headless.js'
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
            dir: 'build/js/',
            reporters: [
                { type: 'html', subdir: 'coverage' },
                { type: 'cobertura', subdir: '.', file: 'coverage.xml' }
            ]
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

        // Which plugins to enable
        plugins: [
            'karma-phantomjs-launcher',
            'karma-jasmine',
            'karma-jasmine-jquery',
            'karma-jasmine-matchers',
            'karma-junit-reporter',
            'karma-coverage'
        ],


        // Continuous Integration mode
        // if true, Karma captures browsers, runs the tests and exits
        singleRun: true
    });
};
