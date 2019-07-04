<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\Test\Api;

use SymfonyCorp\Connect\Api\Api;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as Response;
use PHPUnit\Framework\TestCase;

/**
 * ApitTest.
 *
 * @author Julien Galenski <julien.galenski@gmail.com>
 */
class ApiTest extends TestCase
{
    private $api;
    private $parser;
    private $browser;
    private $logger;
    private $xml;

    public function setUp()
    {
        $this->browser = $this->getMockBuilder('Buzz\\Browser')->getMock();
        $this->parser = $this->getMockBuilder('SymfonyCorp\\Connect\\Api\\Parser\\ParserInterface')->getMock();
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
                      ->method('sendRequest')
                      ->with($this->createRequest('GET', 'http://foobar/api/', ['Accept: application/vnd.com.symfony.connect+xml']))
                      ->will($this->returnValue($this->createResponse()))
        ;

        $object = $this->api->get('http://foobar/api/');
        $this->assertInstanceOf('SymfonyCorp\Connect\Api\Entity\Root', $object);
    }

    public function testGetReturnsTrueIfServerReturns204StatusCode()
    {
        $this->browser->expects($this->once())
                      ->method('sendRequest')
                      ->with($this->createRequest('GET', 'http://foobar/api/', ['Accept: application/vnd.com.symfony.connect+xml']))
                      ->will($this->returnValue($this->createResponse('204')))
        ;

        $this->assertTrue($this->api->get('http://foobar/api/'));
    }

    public function testGetReturnsTrueIfServerReturns201StatusCodeWithAnEmptyResponse()
    {
        $request = $this->createRequest('GET', 'http://foobar/api/', ['Accept: application/vnd.com.symfony.connect+xml']);
        $this->browser->expects($this->once())
                      ->method('sendRequest')
                      ->with($request)
                      ->will($this->returnValue($this->createResponse('201', false)))
        ;

        $this->assertTrue($this->api->get('http://foobar/api/'));
    }

    /**
     * @expectedException \SymfonyCorp\Connect\Exception\ApiClientException
     */
    public function testGetThrowsClientExceptionWhenServerReturns40xStatusCode()
    {
        $this->browser->expects($this->once())
                      ->method('sendRequest')
                      ->with($this->createRequest('GET', 'http://foobar/api/', ['Accept: application/vnd.com.symfony.connect+xml']))
                      ->will($this->returnValue($this->createResponse('400')))
        ;

        $this->api->get('http://foobar/api/');
    }

    /**
     * @expectedException \SymfonyCorp\Connect\Exception\ApiServerException
     */
    public function testGetThrowsServerExceptionWhenServerReturns50xStatusCode()
    {
        $this->browser->expects($this->once())
                      ->method('sendRequest')
                      ->with($this->createRequest('GET', 'http://foobar/api/', ['Accept: application/vnd.com.symfony.connect+xml']))
                      ->will($this->returnValue($this->createResponse('500')))
        ;

        $this->api->get('http://foobar/api/');
    }

    public function testGetAddsAccessTokenToQueryParameter()
    {
        $this->api->setAccessToken('foobar');
        $this->browser->expects($this->once())
            ->method('sendRequest')
            ->with($this->createRequest('GET', 'http://foobar/api/?access_token=foobar', ['Accept: application/vnd.com.symfony.connect+xml']))
            ->will($this->returnValue($this->createResponse()))
        ;

        $this->api->get('http://foobar/api/');
    }

    public function testSubmit()
    {
        $this->api->setAccessToken('foobar');
        $this->browser->expects($this->once())
                      ->method('submitForm')
                      ->with('http://foobar/api/?access_token=foobar', array('foo' => 'bar'), 'POST', array('Accept: application/vnd.com.symfony.connect+xml'))
                      ->will($this->returnValue($this->createResponse('204', false)))
        ;

        $this->api->submit('http://foobar/api/', 'POST', array('foo' => 'bar'));
    }

    /**
     * @expectedException \SymfonyCorp\Connect\Exception\ApiClientException
     */
    public function testSubmitThrowsClientExceptionWhenServerReturns40xStatusCode()
    {
        $this->browser->expects($this->once())
                      ->method('submitForm')
                      ->with('http://foobar/api/')
                      ->will($this->returnValue($this->createResponse('400')))
        ;

        $this->api->submit('http://foobar/api/', 'POST', array('foo' => 'bar'));
    }

    public function testSubmitThrowsClientExceptionAndAddErrorWhenServerReturns40xStatusCode()
    {
        $this->xml = file_get_contents(__DIR__.'/../../../../fixtures/error.xml');
        $this->browser->expects($this->once())
                      ->method('submitForm')
                      ->with('http://foobar/api/')
                      ->will($this->returnValue($this->createResponse('422')))
        ;

        try {
            $this->api->submit('http://foobar/api/', 'POST', array('foo' => 'bar'));
        } catch (\Exception $e) {
            $this->assertInstanceOf('SymfonyCorp\Connect\Exception\ApiClientException', $e);
            $this->assertInstanceOf('SymfonyCorp\Connect\Api\Model\Error', $e->getError());
            $this->assertCount(2, $e->getError()->getEntityBodyParameters());
        }
    }

    /**
     * @expectedException \SymfonyCorp\Connect\Exception\ApiServerException
     */
    public function testSubmitThrowsServerExceptionWhenServerReturns50xStatusCode()
    {
        $this->browser->expects($this->once())
                      ->method('submitForm')
                      ->with('http://foobar/api/')
                      ->will($this->returnValue($this->createResponse('500')))
        ;

        $this->api->submit('http://foobar/api/', 'POST', array('foo' => 'bar'));
    }

    public function getRoot()
    {
        $this->browser->expects($this->once())
                      ->method('sendRequest')
                      ->with($this->createRequest('GET', 'http://foobar/api/', ['Accept: application/vnd.com.symfony.connect+xml']))
                      ->will($this->returnValue($this->createResponse()))
        ;

        $this->assertInstanceOf('SymfonyCorp\Connect\Api\Entity\Root', $this->api->getRoot());
    }

    private function createRequest($method, $url, $headers = [])
    {
        return new Request($method, $url, $headers);
    }

    private function createResponse($statusCode = 200, $content = true)
    {
        if ($content) {
            return new Response($statusCode, [sprintf('HTTP/1.1 %s FOOBAR', $statusCode)], $this->xml);
        }

        return new Response($statusCode, [sprintf('HTTP/1.1 %s FOOBAR', $statusCode)]);
    }
}
