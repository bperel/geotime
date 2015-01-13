<?php

namespace geotime\Test;

use geotime\models\Period;
use PHPUnit_Framework_TestCase;


class PeriodTest extends \PHPUnit_Framework_TestCase {

    static $startDate = '01-01-2012';
    static $endDate   = '01-01-2014';

    static function setUpBeforeClass() {
        Period::$log->info(__CLASS__." tests started");
    }

    static function tearDownAfterClass() {
        Period::$log->info(__CLASS__." tests ended");
    }

    public function testGetStartYear() {
        $p = new Period();
        $p->setStart(new \MongoDate(strtotime(self::$startDate)));
        $this->assertEquals(2012, $p->getStartYear());
    }

    public function testGetEndYear() {
        $p = new Period();
        $p->setEnd(new \MongoDate(strtotime(self::$endDate)));
        $this->assertEquals(2014, $p->getEndYear());
    }

    public function testToStringShort() {

        $p = new Period();
        $p->setStart(new \MongoDate(strtotime(self::$startDate)));
        $p->setEnd(new \MongoDate(strtotime(self::$endDate)));

        $this->assertEquals('2012-2014', $p->__toStringShort());
    }

    public function testToString() {

        $p = new Period();
        $p->setStart(new \MongoDate(strtotime(self::$startDate)));
        $p->setEnd(new \MongoDate(strtotime(self::$endDate)));

        $this->assertEquals('Period 2012 to 2014', $p->__toString());
    }
}
 