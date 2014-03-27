<?php

namespace geotime\Test;

use PHPUnit_Framework_TestCase;
use geotime\Database;


class DatabaseTest extends \PHPUnit_Framework_TestCase {
    public function testConnect() {
        Database::connect(Database::$testDbName);
        $this->assertTrue(Database::$connected);
    }
}
 