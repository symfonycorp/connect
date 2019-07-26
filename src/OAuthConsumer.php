<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use SymfonyCorp\Connect\Exception\OAuthException;

/**
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class OAuthConsumer
{
    const ENDPOINT = 'https://connect.symfony.com';

    private $httpClient;
    private $appId;
    private $appSecret;
    private $scope;
    private $strictChecks = true;
    private $endpoint;
    private $logger;

    protected $paths = [
        'access_token' => '/oauth/access_token',
        'authorize' => '/oauth/authorize',
    ];

    public function __construct(string $appId, string $appSecret, string $scope, string $endpoint = null, HttpClientInterface $httpClient = null, LoggerInterface $logger = null)
    {
        $this->httpClient = $httpClient ?? HttpClient::create();
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->scope = $scope;
        $this->endpoint = $endpoint ?? self::ENDPOINT;
        $this->logger = $logger ?? new NullLogger();
    }

    public function setStrictChecks(bool $strictChecks): void
    {
        $this->strictChecks = $strictChecks;
    }

    public function getAuthorizationUri($callbackUri, $state): string
    {
        $params = [
            'client_id' => $this->appId,
            'scope' => $this->scope,
            'redirect_uri' => $callbackUri,
            'state' => $state,
            'response_type' => 'code',
        ];

        $uri = sprintf('%s%s?%s', $this->endpoint, $this->paths['authorize'], http_build_query($params));

        return $uri;
    }

    public function requestAccessToken(string $callbackUri, string $authorizationCode): array
    {
        $params = [
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'code' => $authorizationCode,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $callbackUri,
            'response_type' => 'code',
            'scope' => $this->scope,
            'strict' => $this->strictChecks,
        ];

        $url = sprintf('%s%s', $this->endpoint, $this->paths['access_token']);

        $this->logger->info(sprintf("Requesting AccessToken to '%s'", $url));
        $this->logger->debug(sprintf('Sent params: %s', json_encode($params)));

        $response = $this->httpClient->request('POST', $url, [
            'body' => $params,
        ]);

        $content = $response->getContent(false);
        $this->logger->debug(sprintf('Response of AccessToken: %s', implode("\r\n", [
            implode("\n", $response->getInfo('response_headers')),
            $content,
        ])));

        try {
            $response = $response->toArray(false);
        } catch (DecodingExceptionInterface $exception) {
            $this->logger->error('Received non-json response.', ['response' => $content]);

            throw new OAuthException('provider', "Response content couldn't be converted to JSON.", $exception);
        }

        if (isset($response['error'])) {
            $this->logger->error('The OAuth2 provider responded with an error', ['response' => $content]);

            $error = $response['error'];
            $message = $response['message'];

            throw new OAuthException($error, $message);
        }

        $token = $response['access_token'];
        $scope = $response['scope'];

        return ['access_token' => $token, 'scope' => $scope];
    }

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function getAppSecret(): string
    {
        return $this->appSecret;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
