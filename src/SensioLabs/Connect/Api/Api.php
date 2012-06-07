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
use SensioLabs\Connect\Api\Parser\ParserInterface;
use SensioLabs\Connect\Api\Parser\VndComSensiolabsConnectXmlParser;
use SensioLabs\Connect\Exception\ApiException;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * Api
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class Api
{
    private $browser;
    private $parser;
    private $logger;
    private $endpoint;
    private $accessToken;

    public function __construct($endpoint = 'https://connect.sensiolabs.com/api', Browser $browser = null, ParserInterface $parser = null, LoggerInterface $logger = null)
    {
        $this->browser = $browser ?: new Browser();
        $this->parser = $parser ?: new VndComSensioLabsConnectXmlParser();
        $this->endpoint = $endpoint;
        $this->logger = $logger;
    }

    public function getRoot($accessToken = null)
    {
        $this->accessToken = $accessToken;
        $response = $this->get($this->endpoint);

        return $response['entity'];
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
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
        $url = $this->constructUrlWithAccessToken($url, $this->accessToken);
        if (null !== $this->logger) {
            $this->logger->info(sprintf('GET %s', $url));
        }

        $response = $this->browser->get($url, array_merge($headers, $this->getAcceptHeader()));

        if (null !== $this->logger) {
            $this->logger->info(sprintf('Status Code %s', $response->getStatusCode()));
            $this->logger->debug($response->getContent());
        }

        $object = $this->parser->parse($response->getContent());
        $object->setApi($this);

        return array('response' => $response, 'entity' => $object);
    }

    public function submit($url, $method = 'POST', array $fields, $accessToken = null, $headers = array())
    {
        $url = $this->constructUrlWithAccessToken($url, $accessToken);
        if (null !== $this->logger) {
            $this->logger->info(sprintf('%s %s', $method, $url));
            $this->logger->debug(sprintf('Posted headers: %s', json_encode($headers)));
            $this->logger->debug(sprintf('Posted fields: %s', json_encode($fields)));
        }

        $response = $this->browser->submit($url, $fields, $method, $headers);

        if (null !== $this->logger) {
            $this->logger->info(sprintf('Status Code %s', $response->getStatusCode()));
            $this->logger->debug(var_export($response->getContent(), true));
        }

        // TODO We might want to return other things...
        return $response;
    }

    private function getAcceptHeader()
    {
        return array('Accept: '.$this->parser->getContentType());
    }

    private function constructUrlWithAccessToken($url)
    {
        if (!$this->accessToken) {
            return $url;
        }

        $parts = parse_url($url);
        $parts['query'] = isset($parts['query']) ? $parts['query'] : null;
        parse_str($parts['query'], $query);
        $query['access_token'] = $this->accessToken;
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

