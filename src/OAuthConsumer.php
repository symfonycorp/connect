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

use Buzz\Browser;
use Buzz\Client\Curl;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use SymfonyCorp\Connect\Exception\OAuthException;

/**
 * OAuthConsumer.
 *
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

    /**
     * @param HttpClientInterface|null $httpClient
     */
    public function __construct($appId, $appSecret, $scope, $endpoint = null, $httpClient = null, LoggerInterface $logger = null)
    {
        if ($httpClient instanceof Browser) {
            @trigger_error(sprintf('Passing a "%s" to "%s()" is deprecated since symfonycorp/connect 5.1, use "%s" instead.', Browser::class, __METHOD__, HttpClientInterface::class), E_USER_DEPRECATED);
            $httpClient = null;
        } elseif (null !== $httpClient && !$httpClient instanceof HttpClientInterface) {
            throw new \TypeError(sprintf('Argument 5 passed to %s() must be an instance of %s or null, %s given', __METHOD__, HttpClientInterface::class, \is_object($httpClient) ? \get_class($httpClient) : \gettype($httpClient)));
        }

        $this->httpClient = $httpClient ?: HttpClient::create();
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->scope = $scope;
        $this->endpoint = $endpoint ?: self::ENDPOINT;
        $this->logger = $logger ?: new NullLogger();
    }

    public function setStrictChecks($strictChecks)
    {
        $this->strictChecks = (bool) $strictChecks;
    }

    /**
     * getAuthorizationUri.
     *
     * @param mixed $callbackUri
     * @param mixed $state
     */
    public function getAuthorizationUri($callbackUri, $state)
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

    /**
     * getAccessToken.
     *
     * @param mixed $callbackUri
     * @param  $authorizationCode
     *
     * @return array
     */
    public function requestAccessToken($callbackUri, $authorizationCode)
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

    public function getAppId()
    {
        return $this->appId;
    }

    public function getAppSecret()
    {
        return $this->appSecret;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @deprecated since symfonycorp/connect 5.1
     */
    public function getBrowser()
    {
        @trigger_error(sprintf('"%s()" is deprecated since symfonycorp/connect 5.1.', __METHOD__), E_USER_DEPRECATED);

        if (!class_exists(Browser::class)) {
            throw new \LogicException(sprintf('You cannot use "%s()" as the "kriswallsmith/buzz" package is not installed, try running "composer require kriswallsmith/buzz".', Browser::class));
        }

        return new Browser(new Curl());
    }

    public function getLogger()
    {
        return $this->logger;
    }
}
