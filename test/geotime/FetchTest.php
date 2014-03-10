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
    public function testGetCommonsInexistantImageURL()
    {
        ob_start();
        $output = Fetch::getCommonsImageURL('inexistant', '.png');
        $echoOutput = ob_get_clean();

        $this->assertStringStartsWith('<b>Error', $echoOutput);
        $this->assertNull($output);
    }
} 