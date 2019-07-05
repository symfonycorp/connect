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

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use SymfonyCorp\Connect\Api\Api;
use SymfonyCorp\Connect\Api\Entity\Root;

/**
 * ApitTest.
 *
 * @author Julien Galenski <julien.galenski@gmail.com>
 */
class ApiTest extends TestCase
{
    private $rootXml;
    private $errorXml;

    public function setUp()
    {
        $this->rootXml = file_get_contents(__DIR__.'/../../../../fixtures/root.xml');
        $this->errorXml = file_get_contents(__DIR__.'/../../../../fixtures/error.xml');
    }

    public function testAccessTokenAccessorsAndMutators()
    {
        $api = $this->createApi(new MockHttpClient());
        $api->setAccessToken('foobar');
        $this->assertEquals('foobar', $api->getAccessToken());
        $api->resetAccessToken();
        $this->assertNull($api->getAccessToken());
    }

    public function testGet()
    {
        $api = $this->createApi(new MockHttpClient(function ($method, $url, $options) {
            $this->assertSame('GET', $method);
            $this->assertSame('http://foobar/api/', $url);
            $this->assertContains('accept: application/vnd.com.symfony.connect+xml', $options['request_headers']);

            return $this->createResponse(200, $this->rootXml);
        }));

        $object = $api->get('http://foobar/api/');
        $this->assertInstanceOf('SymfonyCorp\Connect\Api\Entity\Root', $object);
    }

    public function testGetReturnsTrueIfServerReturns204StatusCode()
    {
        $api = $this->createApi(new MockHttpClient(function ($method, $url) {
            $this->assertSame('GET', $method);
            $this->assertSame('http://foobar/api/', $url);

            return $this->createResponse('204', $this->rootXml);
        }));

        $this->assertTrue($api->get('http://foobar/api/'));
    }

    public function testGetReturnsTrueIfServerReturns201StatusCodeWithAnEmptyResponse()
    {
        $api = $this->createApi(new MockHttpClient(function ($method, $url) {
            $this->assertSame('GET', $method);
            $this->assertSame('http://foobar/api/', $url);

            return $this->createResponse('201', '');
        }));

        $this->assertTrue($api->get('http://foobar/api/'));
    }

    /**
     * @expectedException \SymfonyCorp\Connect\Exception\ApiClientException
     */
    public function testGetThrowsClientExceptionWhenServerReturns40xStatusCode()
    {
        $api = $this->createApi(new MockHttpClient(function ($method, $url) {
            $this->assertSame('GET', $method);
            $this->assertSame('http://foobar/api/', $url);

            return $this->createResponse(400, $this->rootXml);
        }));

        $api->get('http://foobar/api/');
    }

    /**
     * @expectedException \SymfonyCorp\Connect\Exception\ApiServerException
     */
    public function testGetThrowsServerExceptionWhenServerReturns50xStatusCode()
    {
        $api = $this->createApi(new MockHttpClient(function ($method, $url) {
            $this->assertSame('GET', $method);
            $this->assertSame('http://foobar/api/', $url);

            return $this->createResponse(500, '');
        }));

        $api->get('http://foobar/api/');
    }

    public function testGetAddsAccessTokenToQueryParameter()
    {
        $api = $this->createApi(new MockHttpClient(function ($method, $url) {
            $this->assertSame('GET', $method);
            $this->assertSame('http://foobar/api/?access_token=foobar', $url);

            return $this->createResponse(200, $this->rootXml);
        }));

        $api->setAccessToken('foobar');
        $api->get('http://foobar/api/');
    }

    public function testSubmit()
    {
        $api = $this->createApi(new MockHttpClient(function ($method, $url, $options) {
            $this->assertSame('POST', $method);
            $this->assertSame('http://foobar/api/?access_token=foobar', $url);
            $this->assertSame('foo=bar', $options['body']);
            $this->assertContains('accept: application/vnd.com.symfony.connect+xml', $options['request_headers']);

            return $this->createResponse(200, $this->rootXml);
        }));

        $api->setAccessToken('foobar');
        $api->submit('http://foobar/api/', 'POST', ['foo' => 'bar']);
    }

    /**
     * @expectedException \SymfonyCorp\Connect\Exception\ApiClientException
     */
    public function testSubmitThrowsClientExceptionWhenServerReturns40xStatusCode()
    {
        $api = $this->createApi(new MockHttpClient(function ($method, $url) {
            $this->assertSame('POST', $method);
            $this->assertSame('http://foobar/api/', $url);

            return $this->createResponse(400, '');
        }));

        $api->submit('http://foobar/api/', 'POST', ['foo' => 'bar']);
    }

    public function testSubmitThrowsClientExceptionAndAddErrorWhenServerReturns40xStatusCode()
    {
        $api = $this->createApi(new MockHttpClient(function ($method, $url) {
            $this->assertSame('POST', $method);
            $this->assertSame('http://foobar/api/', $url);

            return $this->createResponse(400, $this->errorXml);
        }));

        try {
            $api->submit('http://foobar/api/', 'POST', ['foo' => 'bar']);
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
        $api = $this->createApi(new MockHttpClient(function ($method, $url) {
            $this->assertSame('POST', $method);
            $this->assertSame('http://foobar/api/', $url);

            return $this->createResponse(500, '');
        }));

        $api->submit('http://foobar/api/', 'POST', ['foo' => 'bar']);
    }

    public function getRoot()
    {
        $api = $this->createApi(new MockHttpClient(function ($method, $url) {
            $this->assertSame('GET', $method);
            $this->assertSame('http://foobar/api', $url);

            return $this->createResponse(200, $this->rootXml);
        }));

        $this->assertInstanceOf(Root::class, $api->getRoot());
    }

    private function createApi(HttpClientInterface $httpClient)
    {
        return new Api(
            'http://foobar/api',
            $httpClient,
            null,
            $this->getMockBuilder('Psr\\Log\\LoggerInterface')->getMock()
        );
    }

    private function createResponse(int $statusCode, string $content)
    {
        return new MockResponse($content, [
            'http_code' => $statusCode,
        ]);
    }
}
