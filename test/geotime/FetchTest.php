<?php
namespace geotime\Test;

use PHPUnit_Framework_TestCase;
use geotime\Fetch;

class FetchTest extends \PHPUnit_Framework_TestCase {

    private function getMockedClass($fixtureFilename) {
        $response = file_get_contents('test/geotime/fixtures/xml/'.$fixtureFilename);

        $mock = $this->getMockBuilder('geotime\Fetch')->setMethods(array('getCommonsImageXMLInfo'))->getMock();
        $mock->expects($this->any())
            ->method('getCommonsImageXMLInfo')
            ->will($this->returnValue($response));

        return $mock;
    }

    public function testGetImageURL()
    {
        $mock = $this->getMockedClass('nonexistent.png.xml');

        ob_start();
        $imageURL = $mock->getCommonsImageURL('nonexistent', '.png');
        $echoOutput = ob_get_clean();

        $this->assertNull($imageURL);
        $this->assertStringStartsWith('<b>Error', $echoOutput);
    }
    public function testGetInexistantImageURL()
    {
        $mock = $this->getMockedClass('Wiki-commons.png.xml');

        ob_start();
        $imageURL = $mock->getCommonsImageURL('Wiki-commons', '.png');
        $echoOutput = ob_get_clean();

        $this->assertNotEmpty($imageURL);
        $this->assertEmpty($echoOutput);
    }

    public function testGetCommonsImageURL() {
        $f = new Fetch();
        $xmlInfo = new \SimpleXMLElement($f->getCommonsImageXMLInfo('Wiki-commons', '.png'));
        $fixtureXML = new \SimpleXMLElement(file_get_contents('test/geotime/fixtures/xml/Wiki-commons.png.xml'));
        $this->assertEquals(trim($fixtureXML->file->urls->file), trim($xmlInfo->file->urls->file));
    }
} 