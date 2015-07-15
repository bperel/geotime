var page = require('webpage').create(),
    system = require('system'),
    fs = require('fs'),
    localserver = require('./localserver'),
    svgPath, output;

if (system.args.length < 3 || system.args.length > 5) {
    console.log('Usage: rasterize.js URL output');
    phantom.exit(1);
} else {
    svgPath = fs.workingDirectory + '/' + system.args[1];
    output = system.args[2];
    page.viewportSize = { width: 400, height: 400 };

    if (!fs.isFile(svgPath)) {
        console.log(svgPath+' does not exist');
        phantom.exit(1);
    }

    page.onLoadFinished = function() {
        page.render(output);
        console.log('thumbnail created');
        phantom.exit(0);
    };

    localserver.create();

    page.open('http://'+localserver.url+'/'+system.args[1]);

}