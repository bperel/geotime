<?php

namespace geotime\Test;

use geotime\Util;
use PHPUnit_Framework_TestCase;


class UtilTest extends \PHPUnit_Framework_TestCase {

    static $fixtures_dir_svg = "test/phpunit/_fixtures/svg/";
    static $fixtures_dir_thumbnails = "test/phpunit/_fixtures/thumbnails/";

    static $wikimediaLogoLocation = "http://upload.wikimedia.org/wikipedia/commons/8/81/Wikimedia-logo.svg";
    static $wikimediaLogoFileName = "logo.svg";

    static $simpleSvgFileName = "simple.svg";

    static function setUpBeforeClass() {
        Util::$log->info(__CLASS__." tests started");

        Util::$cache_dir_svg = "test/phpunit/cache/svg/";
        Util::$cache_dir_thumbnails = "test/phpunit/cache/thumbnails/";
        Util::$cache_dir_json = "test/phpunit/cache/json/";

        self::cleanGeneratedFiles();
    }

    static function tearDownAfterClass()
    {
        self::cleanGeneratedFiles();
        Util::$log->info(__CLASS__ . " tests ended");
    }

    static function cleanGeneratedFiles() {
        @unlink(Util::$cache_dir_svg . self::$wikimediaLogoFileName);
        @unlink(Util::$cache_dir_thumbnails . self::$wikimediaLogoFileName . ".png");
        @unlink(Util::$cache_dir_svg . self::$simpleSvgFileName);
        @unlink(Util::$cache_dir_thumbnails . self::$simpleSvgFileName . ".png");
    }

    public function testFetchSvg() {
        $success = Util::fetchSvgWithThumbnail(self::$wikimediaLogoLocation, self::$wikimediaLogoFileName);
        $this->assertTrue($success);
    }

    public function testFetchSvgNullUrl() {
        $sucesss = Util::fetchSvgWithThumbnail(null, '');
        $this->assertFalse($sucesss);
    }

    public function testStoreThumbnailSvgSimple() {
        $this->assertFileNotExists(Util::$cache_dir_thumbnails . self::$simpleSvgFileName . ".png");

        file_put_contents(Util::$cache_dir_svg . self::$simpleSvgFileName, file_get_contents(self::$fixtures_dir_svg . self::$simpleSvgFileName));
        $success = Util::generateThumbnailFromSvg(self::$simpleSvgFileName);

        $this->assertTrue($success);
        $this->assertFileEquals(self::$fixtures_dir_thumbnails . self::$simpleSvgFileName . ".png", Util::$cache_dir_thumbnails . self::$simpleSvgFileName . ".png");
    }

    public function testFetchAndStoreSvgWikiLogo() {
        $this->assertFileNotExists(Util::$cache_dir_svg . self::$wikimediaLogoFileName);

        $success = Util::fetchSvgWithThumbnail(self::$wikimediaLogoLocation, self::$wikimediaLogoFileName, self::$wikimediaLogoFileName);

        $this->assertTrue($success);
        $this->assertFileEquals(self::$fixtures_dir_svg. self::$wikimediaLogoFileName, Util::$cache_dir_svg . self::$wikimediaLogoFileName);
        $this->assertFileEquals(self::$fixtures_dir_thumbnails . self::$wikimediaLogoFileName . ".png", Util::$cache_dir_thumbnails . self::$wikimediaLogoFileName . ".png");
    }

    public function testStoreSvgInvalidPath() {
        $svg = file_get_contents(self::$fixtures_dir_svg.'simple.svg');
        $success = Util::storeSvgWithThumbnail($svg, 'lo/go.svg');

        $this->assertFalse($success);
    }
}
 