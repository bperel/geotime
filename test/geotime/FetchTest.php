<?php
namespace geotime\Test;

use PHPUnit_Framework_TestCase;
use geotime\Fetch;

class FetchTest extends \PHPUnit_Framework_TestCase {
    public function testGetCommonsImageURL()
    {
        $imageFile = Fetch::getCommonsImageURL('Wiki-commons', '.png');
        $this->assertNotNull($imageFile);
    }
} 