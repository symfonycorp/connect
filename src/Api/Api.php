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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use SymfonyCorp\Connect\Api\Entity\Root;
use SymfonyCorp\Connect\Api\Parser\ParserInterface;
use SymfonyCorp\Connect\Api\Parser\VndComSymfonyConnectXmlParser as Parser;
use SymfonyCorp\Connect\Exception\ApiClientException;
use SymfonyCorp\Connect\Exception\ApiParserException;
use SymfonyCorp\Connect\Exception\ApiServerException;

/**
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class Api
{
    const ENDPOINT = 'https://connect.symfony.com/api';

    private $httpClient;
    private $parser;
    private $logger;
    private $endpoint;
    private $accessToken;

    public function __construct(string $endpoint = null, HttpClientInterface $httpClient = null, ParserInterface $parser = null, LoggerInterface $logger = null)
    {
        $this->httpClient = $httpClient ?? HttpClient::create();
        $this->parser = $parser ?? new Parser();
        $this->endpoint = $endpoint ?? self::ENDPOINT;
        $this->logger = $logger ?? new NullLogger();
    }

    public function getRoot(): Root
    {
        return $this->get($this->endpoint);
    }

    /**
     * @return $this
     */
    public function setAccessToken(?string $accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function resetAccessToken(): void
    {
        $this->accessToken = null;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function get($url, $headers = [])
    {
        $url = $this->constructUrlWithAccessToken($url);

        $this->logger->info(sprintf('GET %s', $url));

        return $this->processResponse($this->httpClient->request('GET', $url, [
            'headers' => array_merge($headers, $this->getAcceptHeader()),
        ]));
    }

    public function submit($url, $method = 'POST', array $fields = [], $headers = [])
    {
        $url = $this->constructUrlWithAccessToken($url);

        $this->logger->info(sprintf('%s %s', $method, $url));
        $this->logger->debug(sprintf('Posted headers: %s', json_encode($headers)));
        $this->logger->debug(sprintf('Posted fields: %s', json_encode($fields)));

        return $this->processResponse($this->httpClient->request($method, $url, [
            'headers' => array_merge($headers, $this->getAcceptHeader()),
            'GET' === $method ? 'query' : 'body' => $fields,
        ]));
    }

    public function parse(string $xml): Entity\AbstractEntity
    {
        $entity = $this->parser->parse($xml);
        $entity->setApi($this);

        return $entity;
    }

    private function processResponse(ResponseInterface $response)
    {
        try {
            $this->logger->info('Response:'.implode("\r\n", [
                implode("\n", $response->getInfo('response_headers')),
                $response->getContent(false),
            ]));

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
        } catch (ServerExceptionInterface $exception) {
            throw new ApiServerException($response->getStatusCode(false), $response->getContent(false), $exception->getMessage(), $response->getHeaders(false));
        } catch (ClientExceptionInterface $exception) {
            try {
                $error = $this->parser->parse($response->getContent(false));
                $error = $error instanceof Model\Error ? $error : new Model\Error();
            } catch (ApiParserException $e) {
                throw new ApiClientException($response->getStatusCode(), $response->getContent(false), $e->getMessage(), $response->getHeaders(false), null, $e);
            }

            throw new ApiClientException($response->getStatusCode(false), $response->getContent(false), $exception->getMessage(), $response->getHeaders(false), $error);
        }
    }

    private function getAcceptHeader(): array
    {
        return ['Accept' => $this->parser->getContentType()];
    }

    private function constructUrlWithAccessToken($url): string
    {
        if (!$this->getAccessToken()) {
            return $url;
        }

        $parts = parse_url($url);
        $parts['query'] = $parts['query'] ?? null;
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
