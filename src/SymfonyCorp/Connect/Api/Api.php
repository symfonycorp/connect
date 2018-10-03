<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\Api;

use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Message\Response;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SymfonyCorp\Connect\Api\Parser\ParserInterface;
use SymfonyCorp\Connect\Api\Parser\VndComSymfonyConnectXmlParser as Parser;
use SymfonyCorp\Connect\Exception\ApiClientException;
use SymfonyCorp\Connect\Exception\ApiParserException;
use SymfonyCorp\Connect\Exception\ApiServerException;

/**
 * Api.
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class Api
{
    const ENDPOINT = 'https://connect.symfony.com/api';

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

    public function get($url, $headers = array())
    {
        $url = $this->constructUrlWithAccessToken($url);

        $this->logger->info(sprintf('GET %s', $url));

        return $this->processResponse($this->browser->get($url, array_merge($headers, $this->getAcceptHeader())));
    }

    public function submit($url, $method = 'POST', array $fields, $headers = array())
    {
        $url = $this->constructUrlWithAccessToken($url);

        $this->logger->info(sprintf('%s %s', $method, $url));
        $this->logger->debug(sprintf('Posted headers: %s', json_encode($headers)));
        $this->logger->debug(sprintf('Posted fields: %s', json_encode($fields)));

        return $this->processResponse($this->browser->submit($url, $fields, $method, array_merge($headers, $this->getAcceptHeader())));
    }

    private function processResponse(Response $response)
    {
        $this->logger->info('Response:'.$response);

        if (500 <= $response->getStatusCode()) {
            throw new ApiServerException($response->getStatusCode(), $response->getContent(), $response->getReasonPhrase(), $response->getHeaders());
        }

        if (400 <= $response->getStatusCode()) {
            try {
                $error = $this->parser->parse($response->getContent());
                $error = $error instanceof Model\Error ? $error : new Model\Error();
            } catch (ApiParserException $e) {
                throw new ApiClientException($response->getStatusCode(), $response->getContent(), $response->getReasonPhrase(), $response->getHeaders(), null, $e);
            }

            throw new ApiClientException($response->getStatusCode(), $response->getContent(), $response->getReasonPhrase(), $response->getHeaders(), $error);
        }

        if (204 === $response->getStatusCode()) {
            return true;
        }

        $content = trim($response->getContent());
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
}
