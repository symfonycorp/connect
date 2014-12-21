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

use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Message\Request;
use Guzzle\Plugin\Mock\MockPlugin;
use SensioLabs\Connect\Api\Api;
use SensioLabs\Connect\Exception\ApiClientException;
use SensioLabs\Connect\Exception\ApiServerException;

/**
 * ApitTest.
 *
 * @author Julien Galenski <julien.galenski@gmail.com>
 */
class ApiTest extends \PHPUnit_Framework_TestCase
{
    /** @var Api */
    private $api;
    private $parser;
    /** @var MockPlugin */
    private $plugin;

    public function setUp()
    {
        $this->browser = new Client();
        $this->plugin = new MockPlugin();
        $this->browser->addSubscriber($this->plugin);

        $this->parser = $this->getMock('SensioLabs\\Connect\\Api\\Parser\\ParserInterface');
        $this->logger = $this->getMock('Psr\\Log\\LoggerInterface');
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
        $this->plugin->addResponse($this->createResponse());
        $object = $this->api->get('http://foobar/api/');
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Root', $object);
        $this->assertUniqueResponse('http://foobar/api/', 'GET');
    }

    public function testGetReturnsTrueIfServerReturns204StatusCode()
    {
        $this->plugin->addResponse($this->createResponse(204));
        $this->assertTrue($this->api->get('http://foobar/api/'));
        $this->assertUniqueResponse('http://foobar/api/', 'GET');
    }

    public function testGetReturnsTrueIfServerReturns201StatusCodeWithAnEmptyResponse()
    {
        $this->plugin->addResponse($this->createResponse(201, false));
        $this->assertTrue($this->api->get('http://foobar/api/'));
        $this->assertUniqueResponse('http://foobar/api/', 'GET');
    }

    public function testGetThrowsClientExceptionWhenServerReturns40xStatusCode()
    {
        $this->plugin->addResponse($this->createResponse(400));

        try {
            $this->api->get('http://foobar/api/');
            $this->fail('An exception should have been raised.');
        } catch (ApiClientException $e) {
        }

        $this->assertUniqueResponse('http://foobar/api/', 'GET');
    }

    public function testGetThrowsServerExceptionWhenServerReturns50xStatusCode()
    {
        $this->plugin->addResponse($this->createResponse(500));

        try {
            $this->api->get('http://foobar/api/');
            $this->fail('An exception should have been raised.');
        } catch (ApiServerException $e) {
        }

        $this->assertUniqueResponse('http://foobar/api/', 'GET');
    }

    public function testGetAddsAccessTokenToQueryParameter()
    {
        $this->plugin->addResponse($this->createResponse());
        $this->api->setAccessToken('foobar');
        $this->api->get('http://foobar/api/');
        $this->assertUniqueResponse('http://foobar/api/?access_token=foobar', 'GET');
    }

    public function testSubmit()
    {
        $this->plugin->addResponse($this->createResponse());
        $this->api->setAccessToken('foobar');
        $this->api->submit('http://foobar/api/', 'POST', array('foo' => 'bar'));
        $this->assertUniqueResponse('http://foobar/api/?access_token=foobar', 'POST', array('foo' => 'bar'));
    }

    public function testSubmitThrowsClientExceptionWhenServerReturns40xStatusCode()
    {
        $this->plugin->addResponse($this->createResponse(400));

        try {
            $this->api->submit('http://foobar/api/', 'POST', array('foo' => 'bar'));
            $this->fail('An exception should have been raised.');
        } catch (ApiClientException $e) {
        }

        $this->assertUniqueResponse('http://foobar/api/', 'POST', array('foo' => 'bar'));
    }

    public function testSubmitThrowsClientExceptionAndAddErrorWhenServerReturns40xStatusCode()
    {
        $this->xml = file_get_contents(__DIR__.'/../../../../fixtures/error.xml');
        $this->plugin->addResponse($this->createResponse(422));

        try {
            $this->api->submit('http://foobar/api/', 'POST', array('foo' => 'bar'));
            $this->fail('An exception should have been raised.');
        } catch (ApiClientException $e) {
            $this->assertInstanceOf('SensioLabs\Connect\Api\Model\Error', $e->getError());
            $this->assertCount(2, $e->getError()->getEntityBodyParameters());
        }

        $this->assertUniqueResponse('http://foobar/api/', 'POST', array('foo' => 'bar'));
    }

    public function testSubmitThrowsServerExceptionWhenServerReturns50xStatusCode()
    {
        $this->plugin->addResponse($this->createResponse(500));

        try {
            $this->api->submit('http://foobar/api/', 'POST', array('foo' => 'bar'));
            $this->fail('An exception should have been raised.');
        } catch (ApiServerException $e) {
        }

        $this->assertUniqueResponse('http://foobar/api/', 'POST', array('foo' => 'bar'));
    }

    public function getRoot()
    {
        $this->plugin->addResponse($this->createResponse());
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Root', $this->api->getRoot());
        $this->assertUniqueResponse('http://foobar/api/', 'GET');
    }

    private function assertUniqueResponse($url, $method, array $fields = array())
    {
        $requests = $this->plugin->getReceivedRequests();
        $this->assertCount(1, $requests);
        /** @var Request $request */
        $request = array_pop($requests);

        $this->assertSame($url, $request->getUrl());
        $this->assertSame(array('application/vnd.com.sensiolabs.connect+xml'), $request->getHeader('Accept')->toArray());
        $this->assertSame($method, $request->getMethod());

        foreach ($fields as $key => $value) {
            $this->assertSame($value, $request->getPostField($key));
        }
    }

    private function createResponse($statusCode = 200, $content = true)
    {
        return new Response($statusCode, null, $content ? $this->xml : null);
    }
}
