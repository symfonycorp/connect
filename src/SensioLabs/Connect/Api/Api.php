<?php

/*
 * This file is part of the SensioLabs Connect package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Connect\Api;

use Buzz\Browser;
use Buzz\Client\Curl;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SensioLabs\Connect\Api\Parser\ParserInterface;
use SensioLabs\Connect\Api\Parser\VndComSensiolabsConnectXmlParser as Parser;
use SensioLabs\Connect\Exception\ApiClientException;
use SensioLabs\Connect\Exception\ApiParserException;
use SensioLabs\Connect\Exception\ApiServerException;

/**
 * Api.
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class Api
{
    const ENDPOINT = 'https://connect.sensiolabs.com/api';

    private $browser;
    private $parser;
    private $logger;
    private $endpoint;
    private $accessToken;

    public function __construct($endpoint = null, Browser $browser = null, ParserInterface $parser = null, LoggerInterface $logger = null)
    {
        $this->browser = $browser ?: new Browser(new Curl());
        $this->parser = $parser ?: new Parser();
        $this->endpoint = $endpoint ?: self::ENDPOINT;
        $this->logger = $logger ?: new NullLogger();
    }

    public function getRoot()
    {
        return $this->get($this->endpoint);
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function resetAccessToken()
    {
        $this->accessToken = null;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function get($url, $headers = [])
    {
        $url = $this->constructUrlWithAccessToken($url);

        $this->logger->info(sprintf('GET %s', $url));

        return $this->processResponse($this->browser->sendRequest(new Request('GET', $url, array_merge($headers, $this->getAcceptHeader()))));
    }

    public function submit($url, $method = 'POST', array $fields = [], $headers = [])
    {
        $url = $this->constructUrlWithAccessToken($url);

        $this->logger->info(sprintf('%s %s', $method, $url));
        $this->logger->debug(sprintf('Posted headers: %s', json_encode($headers)));
        $this->logger->debug(sprintf('Posted fields: %s', json_encode($fields)));

        return $this->processResponse($this->browser->submitForm($url, $fields, $method, array_merge($headers, $this->getAcceptHeader())));
    }

    private function processResponse(ResponseInterface $response)
    {
        $this->logger->info('Response:'.$this->getStringResponse($response));

        if (500 <= $response->getStatusCode()) {
            throw new ApiServerException($response->getStatusCode(), $response->getBody(), $response->getReasonPhrase(), $response->getHeaders());
        }

        if (400 <= $response->getStatusCode()) {
            try {
                $error = $this->parser->parse($response->getBody());
                $error = $error instanceof Model\Error ? $error : new Model\Error();
            } catch (ApiParserException $e) {
                throw new ApiClientException($response->getStatusCode(), $response->getBody(), $response->getReasonPhrase(), $response->getHeaders(), null, $e);
            }

            throw new ApiClientException($response->getStatusCode(), $response->getBody(), $response->getReasonPhrase(), $response->getHeaders(), $error);
        }

        if (204 === $response->getStatusCode()) {
            return true;
        }

        $content = trim($response->getBody());
        if (empty($content)) {
            return true;
        }

        $object = $this->parser->parse($content);
        $object->setApi($this);

        return $object;
    }

    private function getAcceptHeader()
    {
        return array('Accept: '.$this->parser->getContentType());
    }

    private function constructUrlWithAccessToken($url)
    {
        if (!$this->getAccessToken()) {
            return $url;
        }

        $parts = parse_url($url);
        $parts['query'] = isset($parts['query']) ? $parts['query'] : null;
        parse_str($parts['query'], $query);
        $query['access_token'] = $this->getAccessToken();
        $parts['query'] = http_build_query($query);

        $url = $parts['scheme'].'://'.$parts['host'];
        if (isset($parts['port'])) {
            $url .= ':'.$parts['port'];
        }
        if (isset($parts['path'])) {
            $url .= $parts['path'];
        }
        if (isset($parts['query'])) {
            $url .= '?'.$parts['query'];
        }

        return $url;
    }

    private function getStringResponse(ResponseInterface $response)
    {
        $headers = $response->getHeaders();

        $headers = array_map(function ($name, array $values) {
            return sprintf('%s: %s', $name, implode(',', $values));
        }, array_keys($headers), $headers);


        return sprintf("\r\n%s\r\n%s\r\n", implode("\r\n", $headers), (string) $response->getBody());
    }
}
