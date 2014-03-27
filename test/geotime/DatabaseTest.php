<?php

namespace geotime\Test;

use geotime\Database;
use PHPUnit_Framework_TestCase;


class DatabaseTest extends \PHPUnit_Framework_TestCase {
    public function testConnect() {
        Database::connect(Database::$testDbName);
        $this->assertTrue(Database::$connected);
    }
}
 