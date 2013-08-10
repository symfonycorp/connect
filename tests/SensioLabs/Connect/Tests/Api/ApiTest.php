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

use Guzzle\Http\Message\Response;
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
        $this->client = $this->getMock('Guzzle\\Http\\Client');
        $this->parser = $this->getMock('SensioLabs\\Connect\\Api\\Parser\\ParserInterface');
        $this->logger = $this->getMock('Symfony\\Component\\HttpKernel\\Log\\LoggerInterface');
        $this->xml = file_get_contents(__DIR__.'/../../../../fixtures/root.xml');

        $this->api = new Api('http://foobar/api', $this->client, null, $this->logger);
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
        $this->client->expects($this->once())
                      ->method('get')
                      ->with('http://foobar/api/', array('Accept: application/vnd.com.sensiolabs.connect+xml'))
                      ->will($this->returnValue($this->createRequestWithResponse()));

        $object = $this->api->get('http://foobar/api/');
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Root', $object);
    }

    public function testGetReturnsTrueIfServerReturns204StatusCode()
    {
        $this->client->expects($this->once())
                      ->method('get')
                      ->with('http://foobar/api/')
                      ->will($this->returnValue($this->createRequestWithResponse('204')));

        $this->assertTrue($this->api->get('http://foobar/api/'));
    }

    public function testGetReturnsTrueIfServerReturns201StatusCodeWithAnEmptyResponse()
    {
        $this->client->expects($this->once())
                      ->method('get')
                      ->with('http://foobar/api/')
                      ->will($this->returnValue($this->createRequestWithResponse('201', false)));

        $this->assertTrue($this->api->get('http://foobar/api/'));
    }

    /**
     * @expectedException SensioLabs\Connect\Exception\ApiClientException
     */
    public function testGetThrowsClientExceptionWhenServerReturns40xStatusCode()
    {
        $this->client->expects($this->once())
                      ->method('get')
                      ->with('http://foobar/api/')
                      ->will($this->returnValue($this->createRequestWithResponse('400')));

        $this->api->get('http://foobar/api/');
    }

    /**
     * @expectedException SensioLabs\Connect\Exception\ApiServerException
     */
    public function testGetThrowsServerExceptionWhenServerReturns50xStatusCode()
    {
        $this->client->expects($this->once())
                      ->method('get')
                      ->with('http://foobar/api/')
                      ->will($this->returnValue($this->createRequestWithResponse('500')));

        $this->api->get('http://foobar/api/');
    }

    public function testGetAddsAccessTokenToQueryParameter()
    {
        $this->api->setAccessToken('foobar');
        $this->client->expects($this->once())
                      ->method('get')
                      ->with('http://foobar/api/?access_token=foobar')
                      ->will($this->returnValue($this->createRequestWithResponse()));

        $this->api->get('http://foobar/api/');
    }

    public function testSubmit()
    {
        $this->api->setAccessToken('foobar');
        $this->client->expects($this->once())
                      ->method('createRequest')
                      ->with('POST', 'http://foobar/api/?access_token=foobar', array('Accept: application/vnd.com.sensiolabs.connect+xml'), array('foo' => 'bar'))
                      ->will($this->returnValue($this->createRequestWithResponse('204', false)));

        $this->api->submit('http://foobar/api/', 'POST', array('foo' => 'bar'));
    }

    /**
     * @expectedException SensioLabs\Connect\Exception\ApiClientException
     */
    public function testSubmitThrowsClientExceptionWhenServerReturns40xStatusCode()
    {
        $this->client->expects($this->once())
                      ->method('createRequest')
                      ->with('POST', 'http://foobar/api/')
                      ->will($this->returnValue($this->createRequestWithResponse('400')));

        $this->api->submit('http://foobar/api/', 'POST', array('foo' => 'bar'));
    }

    public function testSubmitThrowsClientExceptionAndAddErrorWhenServerReturns40xStatusCode()
    {
        $response = $this->createResponse('422');
        $response->setBody(file_get_contents(__DIR__.'/../../../../fixtures/error.xml'));

        $request = $this->getMock('Guzzle\Http\Message\Request', array(), array(), '', false);
        $request
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $this->client->expects($this->once())
                      ->method('createRequest')
                      ->with('POST', 'http://foobar/api/')
                      ->will($this->returnValue($request));

        try {
            $this->api->submit('http://foobar/api/', 'POST', array('foo' => 'bar'));
        } catch (\Exception $e) {
            $this->assertInstanceOf('SensioLabs\Connect\Exception\ApiClientException', $e);
            $this->assertInstanceOf('SensioLabs\Connect\Api\Model\Error', $e->getError());
            $this->assertCount(2, $e->getError()->getEntityBodyParameters());
        }
    }

    /**
     * @expectedException SensioLabs\Connect\Exception\ApiServerException
     */
    public function testSubmitThrowsServerExceptionWhenServerReturns50xStatusCode()
    {
        $this->client->expects($this->once())
                      ->method('createRequest')
                      ->with('POST', 'http://foobar/api/')
                      ->will($this->returnValue($this->createRequestWithResponse('500')));

        $this->api->submit('http://foobar/api/', 'POST', array('foo' => 'bar'));
    }

    public function getRoot()
    {
        $this->client->expects($this->once())
                      ->method('get')
                      ->with('http://foobar/api/')
                      ->will($this->returnValue($this->createRequestWithResponse()));

        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Root', $this->api->getRoot());
    }

    private function createRequestWithResponse($statusCode = 200, $content = true)
    {
        $request = $this->getMock('Guzzle\Http\Message\Request', array(), array(), '', false);
        $request
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue($this->createResponse($statusCode, $content)))
        ;

        return $request;
    }

    private function createResponse($statusCode = 200, $content = true)
    {
        $response = new Response($statusCode);
        $response
            ->setStatus($statusCode, 'FOOBAR')
            ->setProtocol('HTTP', '1.1')
        ;

        if ($content) {
            $response->setBody($this->xml);
        }

        return $response;
    }
}
