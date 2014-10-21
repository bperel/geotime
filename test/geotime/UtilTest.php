<?php

namespace geotime\Test;

use geotime\Util;
use PHPUnit_Framework_TestCase;


class UtilTest extends \PHPUnit_Framework_TestCase {

    static $fixtures_dir_svg = "test/geotime/_fixtures/svg/";
    static $fixtures_dir_thumbnails = "test/geotime/_fixtures/thumbnails/";
    static $cachedFileName = "logo.svg";

    static $wikimediaLogoLocation = "http://upload.wikimedia.org/wikipedia/commons/8/81/Wikimedia-logo.svg";
    static $wikimediaLogoFileName = "logo.svg";

    static function setUpBeforeClass() {
        Util::$log->info(__CLASS__." tests started");

        Util::$cache_dir_svg = "test/geotime/cache/svg/";
        Util::$cache_dir_thumbnails = "test/geotime/cache/thumbnails/";
        Util::$cache_dir_json = "test/geotime/cache/json/";

        @unlink(Util::$cache_dir_svg . self::$cachedFileName);
        @unlink(Util::$cache_dir_thumbnails . self::$cachedFileName . ".png");
    }

    static function tearDownAfterClass()
    {
        @unlink(Util::$cache_dir_svg . self::$cachedFileName);
        @unlink(Util::$cache_dir_thumbnails . self::$cachedFileName . ".png");

        Util::$log->info(__CLASS__ . " tests ended");
    }

    public function testFetchSvg() {
        $success = Util::fetchSvgWithThumbnail(self::$wikimediaLogoLocation, self::$wikimediaLogoFileName);
        $this->assertTrue($success);
    }

    public function testFetchSvgNullUrl() {
        $sucesss = Util::fetchSvgWithThumbnail(null, '');
        $this->assertFalse($sucesss);
    }

    public function testFetchAndStoreSvg() {
        $this->assertFileNotExists(Util::$cache_dir_svg . self::$cachedFileName);

        $success = Util::fetchSvgWithThumbnail(self::$wikimediaLogoLocation, self::$wikimediaLogoFileName, self::$cachedFileName);

        $this->assertTrue($success);
        $this->assertFileEquals(self::$fixtures_dir_svg. self::$cachedFileName, Util::$cache_dir_svg . self::$cachedFileName);
        $this->assertFileEquals(self::$fixtures_dir_thumbnails . self::$cachedFileName . ".png", Util::$cache_dir_thumbnails . self::$cachedFileName . ".png");
    }

    public function testStoreSvgInvalidPath() {
        $svg = file_get_contents(self::$fixtures_dir_svg.'simple.svg');
        $thumbnail = file_get_contents(self::$fixtures_dir_thumbnails.'simple.svg.png');
        $success = Util::storeSvgWithThumbnail($svg, $thumbnail, 'lo/go.svg');

        $this->assertFalse($success);
    }
}
 