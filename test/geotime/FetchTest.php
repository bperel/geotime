<?php
namespace geotime\Test;

use PHPUnit_Framework_TestCase;
use geotime\Fetch;

class FetchTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    var $mock;

    protected function setUp() {
        $this->mock = $this->getMockBuilder('geotime\Fetch')
            ->setMethods(array('getCommonsImageXMLInfo'))
            ->getMock();
    }

    private function setCommonsXMLFixture($fixtureFilename) {
        $response = file_get_contents('test/geotime/fixtures/xml/'.$fixtureFilename);

        $this->mock->expects($this->any())
            ->method('getCommonsImageXMLInfo')
            ->will($this->returnValue($response));
    }

    /* Tests */

    public function testGetImageURL()
    {
        $this->setCommonsXMLFixture('nonexistent.png.xml');

        ob_start();
        $imageURL = $this->mock->getCommonsImageURL('nonexistent', '.png');
        $echoOutput = ob_get_clean();

        $this->assertNull($imageURL);
        $this->assertStringStartsWith('<b>Error', $echoOutput);
    }
    public function testGetInexistantImageURL()
    {
        $this->setCommonsXMLFixture('Wiki-commons.png.xml');

        ob_start();
        $imageURL = $this->mock->getCommonsImageURL('Wiki-commons', '.png');
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