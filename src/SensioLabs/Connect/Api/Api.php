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

use Guzzle\Http\Client as Guzzle;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Url;
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

    private $client;
    private $parser;
    private $logger;
    private $endpoint;
    private $accessToken;

    /**
     * @param string            $endpoint The API enpoint
     * @param null|array|Guzzle $client   Either a Guzzle client or an array of options
     * @param ParserInterface   $parser   A parser
     * @param LoggerInterface   $logger   A logger
     */
    public function __construct($endpoint = null, $client = null, ParserInterface $parser = null, LoggerInterface $logger = null)
    {
        if ($client instanceof Guzzle) {
            $this->client = $client;
        } elseif (is_array($client)) {
            $this->client = \SensioLabs\Connect\createClient(self::ENDPOINT, $client);
        } else {
            $this->client = \SensioLabs\Connect\createClient(self::ENDPOINT, array());
        }

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

        return $this->processResponse($this->client->get($url, array_merge($headers, $this->getAcceptHeader()))->send());
    }

    public function post($url, array $fields, $headers = array())
    {
        $url = $this->constructUrlWithAccessToken($url);

        $this->logger->info(sprintf('POST %s', $url));
        $this->logger->debug(sprintf('Posted headers: %s', json_encode($headers)));
        $this->logger->debug(sprintf('Posted fields: %s', json_encode($fields)));

        $request = $this->client->post($url, array_merge($headers, $this->getAcceptHeader()), null);
        $request->addPostFields($fields);

        return $this->processResponse($request->send());
    }

    /**
     * @deprecated Use either get or post methods
     */
    public function submit($url, $method = 'POST', array $fields, $headers = array())
    {
        switch (strtolower($method)) {
            case 'GET':
                return $this->get($url, $headers);
            case 'POST':
                return $this->post($url, $fields, $headers);
            default:
                throw new \InvalidArgumentException(sprintf('Method %s is not supported.'));
        }
    }

    private function processResponse(Response $response)
    {
        $this->logger->info(sprintf('Status Code %s', $response->getStatusCode()));
        $this->logger->debug(var_export($response->getBody(true), true));

        if (500 <= $response->getStatusCode()) {
            throw new ApiServerException($response->getStatusCode(), $response->getBody(true), $response->getReasonPhrase(), $response->getHeaders()->toArray());
        }

        if (400 <= $response->getStatusCode()) {
            try {
                $error = $this->parser->parse($response->getBody(true));
                $error = $error instanceof Model\Error ? $error : new Model\Error();
            } catch (ApiParserException $e) {
                throw new ApiClientException($response->getStatusCode(), $response->getBody(true), $response->getReasonPhrase(), $response->getHeaders()->toArray(), null, $e);
            }

            throw new ApiClientException($response->getStatusCode(), $response->getBody(true), $response->getReasonPhrase(), $response->getHeaders()->toArray(), $error);
        }

        if (204 === $response->getStatusCode()) {
            return true;
        }

        $content = trim($response->getBody(true));
        if (empty($content)) {
            return true;
        }

        $object = $this->parser->parse($content);
        $object->setApi($this);

        return $object;
    }

    private function getAcceptHeader()
    {
        return array('Accept' => $this->parser->getContentType());
    }

    private function constructUrlWithAccessToken($url)
    {
        if (!$this->getAccessToken()) {
            return $url;
        }

        $url = Url::factory($url);
        $url->getQuery()->add('access_token', $this->getAccessToken());

        return (string) $url;
    }
}
