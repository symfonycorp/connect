<?php

/*
 * This file is part of the SensioLabs Connect package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Connect\Test\Api;

use Buzz\Message\Response;
use SensioLabs\Connect\Api\Api;

/**
 * ApitTest.
 *
 * @author Julien Galenski <julien.galenski@gmail.com>
 */
class ApiTest extends \PHPUnit_Framework_TestCase
{
    private $api;
    private $parser;

    public function setUp()
    {
        $this->browser = $this->getMockBuilder('Buzz\\Browser')->getMock();
        $this->parser = $this->getMockBuilder('SensioLabs\\Connect\\Api\\Parser\\ParserInterface')->getMock();
        $this->logger = $this->getMockBuilder('Psr\\Log\\LoggerInterface')->getMock();
        $this->xml = file_get_contents(__DIR__.'/../../../../fixtures/root.xml');

        $this->api = new Api('http://foobar/api', $this->browser, null, $this->logger);
    }

    public function testAccessTokenAccessorsAndMutators()
    {
        $this->api->setAccessToken('foobar');
        $this->assertEquals('foobar', $this->api->getAccessToken());
        $this->api->resetAccessToken();
        $this->assertNull($this->api->getAccessToken());
    }

    public function testGet()
    {
        $this->browser->expects($this->once())
                      ->method('get')
                      ->with('http://foobar/api/', array('Accept: application/vnd.com.sensiolabs.connect+xml'))
                      ->will($this->returnValue($this->createResponse()));

        $object = $this->api->get('http://foobar/api/');
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Root', $object);
    }

    public function testGetReturnsTrueIfServerReturns204StatusCode()
    {
        $this->browser->expects($this->once())
                      ->method('get')
                      ->with('http://foobar/api/')
                      ->will($this->returnValue($this->createResponse('204')));

        $this->assertTrue($this->api->get('http://foobar/api/'));
    }

    public function testGetReturnsTrueIfServerReturns201StatusCodeWithAnEmptyResponse()
    {
        $this->browser->expects($this->once())
                      ->method('get')
                      ->with('http://foobar/api/')
                      ->will($this->returnValue($this->createResponse('201', false)));

        $this->assertTrue($this->api->get('http://foobar/api/'));
    }

    /**
     * @expectedException \SensioLabs\Connect\Exception\ApiClientException
     */
    public function testGetThrowsClientExceptionWhenServerReturns40xStatusCode()
    {
        $this->browser->expects($this->once())
                      ->method('get')
                      ->with('http://foobar/api/')
                      ->will($this->returnValue($this->createResponse('400')));

        $this->api->get('http://foobar/api/');
    }

    /**
     * @expectedException \SensioLabs\Connect\Exception\ApiServerException
     */
    public function testGetThrowsServerExceptionWhenServerReturns50xStatusCode()
    {
        $this->browser->expects($this->once())
                      ->method('get')
                      ->with('http://foobar/api/')
                      ->will($this->returnValue($this->createResponse('500')));

        $this->api->get('http://foobar/api/');
    }

    public function testGetAddsAccessTokenToQueryParameter()
    {
        $this->api->setAccessToken('foobar');
        $this->browser->expects($this->once())
                      ->method('get')
                      ->with('http://foobar/api/?access_token=foobar')
                      ->will($this->returnValue($this->createResponse()));

        $this->api->get('http://foobar/api/');
    }

    public function testSubmit()
    {
        $this->api->setAccessToken('foobar');
        $this->browser->expects($this->once())
                      ->method('submit')
                      ->with('http://foobar/api/?access_token=foobar', array('foo' => 'bar'), 'POST', array('Accept: application/vnd.com.sensiolabs.connect+xml'))
                      ->will($this->returnValue($this->createResponse('204', false)));

        $this->api->submit('http://foobar/api/', 'POST', array('foo' => 'bar'));
    }

    /**
     * @expectedException \SensioLabs\Connect\Exception\ApiClientException
     */
    public function testSubmitThrowsClientExceptionWhenServerReturns40xStatusCode()
    {
        $this->browser->expects($this->once())
                      ->method('submit')
                      ->with('http://foobar/api/')
                      ->will($this->returnValue($this->createResponse('400')));

        $this->api->submit('http://foobar/api/', 'POST', array('foo' => 'bar'));
    }

    public function testSubmitThrowsClientExceptionAndAddErrorWhenServerReturns40xStatusCode()
    {
        $response = $this->createResponse('422');
        $response->setContent(file_get_contents(__DIR__.'/../../../../fixtures/error.xml'));
        $this->browser->expects($this->once())
                      ->method('submit')
                      ->with('http://foobar/api/')
                      ->will($this->returnValue($response));

        try {
            $this->api->submit('http://foobar/api/', 'POST', array('foo' => 'bar'));
        } catch (\Exception $e) {
            $this->assertInstanceOf('SensioLabs\Connect\Exception\ApiClientException', $e);
            $this->assertInstanceOf('SensioLabs\Connect\Api\Model\Error', $e->getError());
            $this->assertCount(2, $e->getError()->getEntityBodyParameters());
        }
    }

    /**
     * @expectedException \SensioLabs\Connect\Exception\ApiServerException
     */
    public function testSubmitThrowsServerExceptionWhenServerReturns50xStatusCode()
    {
        $this->browser->expects($this->once())
                      ->method('submit')
                      ->with('http://foobar/api/')
                      ->will($this->returnValue($this->createResponse('500')));

        $this->api->submit('http://foobar/api/', 'POST', array('foo' => 'bar'));
    }

    public function getRoot()
    {
        $this->browser->expects($this->once())
                      ->method('get')
                      ->with('http://foobar/api/')
                      ->will($this->returnValue($this->createResponse()));

        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Root', $this->api->getRoot());
    }

    private function createResponse($statusCode = 200, $content = true)
    {
        $response = new Response();
        $response->setHeaders(array(sprintf('HTTP/1.1 %s FOOBAR', $statusCode)));
        if ($content) {
            $response->setContent($this->xml);
        }

        return $response;
    }
}
