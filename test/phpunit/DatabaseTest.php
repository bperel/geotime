<?php

namespace geotime\Test;

use geotime\helpers\ModelHelper;
use geotime\Test\Helper\MariaDbTestHelper;


class DatabaseTest extends MariaDbTestHelper {

    /* Tests */

    public function testConnect() {
        try {
            ModelHelper::getEm()->getConnection()->connect();
        } catch (\Exception $e) {
            $this->fail();
        }
    }
}

