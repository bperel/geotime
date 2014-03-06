<?php

namespace geotime\Test;

use geotime\Database;


class DatabaseTest extends \PHPUnit_Framework_TestCase {
    public function testConnect() {
        $this->assertNotNull(Database::$db);
    }
}
 