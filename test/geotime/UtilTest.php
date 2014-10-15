<?php

namespace geotime\Test;

use geotime\Util;
use PHPUnit_Framework_TestCase;


class UtilTest extends \PHPUnit_Framework_TestCase {

    static $fixtures_dir_svg = "test/geotime/_fixtures/svg/";
    static $cachedFileName = "logo.svg";
    static $wikimediaLogoLocation = "http://upload.wikimedia.org/wikipedia/commons/8/81/Wikimedia-logo.svg";

    static function setUpBeforeClass() {
        Util::$log->info(__CLASS__." tests started");

        Util::$cache_dir_svg = "test/geotime/cache/svg/";
        Util::$cache_dir_json = "test/geotime/cache/json/";

        @unlink(Util::$cache_dir_svg . self::$cachedFileName);
    }

    static function tearDownAfterClass()
    {
        @unlink(Util::$cache_dir_svg . self::$cachedFileName);
        Util::$log->info(__CLASS__ . " tests ended");
    }

    public function testFetchSvg() {
        $success = Util::fetchSvg(self::$wikimediaLogoLocation);
        $this->assertTrue($success);
    }

    public function testFetchSvgNullUrl() {
        $sucesss = Util::fetchSvg(null);
        $this->assertFalse($sucesss);
    }

    public function testFetchAndStoreSvg() {
        $this->assertFileNotExists(Util::$cache_dir_svg . self::$cachedFileName);

        $success = Util::fetchSvg(self::$wikimediaLogoLocation, self::$cachedFileName);

        $this->assertTrue($success);
        $this->assertFileExists(Util::$cache_dir_svg . self::$cachedFileName);
    }

    public function testStoreSvgInvalidPath() {
        $svg = file_get_contents(self::$fixtures_dir_svg.'simple.svg');
        $success = Util::storeSvg($svg, 'lo/go.svg');

        $this->assertFalse($success);
    }
}
 