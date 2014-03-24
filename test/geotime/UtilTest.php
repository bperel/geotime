<?php

namespace geotime\Test;

use PHPUnit_Framework_TestCase;
use geotime\Util;


class UtilTest extends \PHPUnit_Framework_TestCase {

    /* This test retrieves the live Wikimedia logo */
    public function testFetchImage() {
        $success = Util::fetchImage("http://upload.wikimedia.org/wikipedia/commons/8/81/Wikimedia-logo.svg");
        $this->assertTrue($success);
    }
}
 