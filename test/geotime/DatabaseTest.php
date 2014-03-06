<?php

namespace geotime\Test;

use geotime\Database;
include_once('../../lib/geotime/Database.php');


class DatabaseTest extends \PHPUnit_Framework_TestCase {
    public function testConnect() {
        $this->assertNotNull(Database::$db);
    }
}
 