<?php

/*
 * This file is part of the SensioLabs Connect package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Connect;

use Buzz\Browser;
use Buzz\Client\Curl;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SensioLabs\Connect\Exception\OAuthException;

/**
 * OAuthConsumer.
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class OAuthConsumer
{
    const ENDPOINT = 'https://connect.sensiolabs.com';

    private $browser;
    private $appId;
    private $appSecret;
    private $scope;
    private $strictChecks = true;
    private $endpoint;
    private $logger;

    protected $paths = array(
        'access_token'   => '/oauth/access_token',
        'authorize'      => '/oauth/authorize',
    );

    public function __construct($appId, $appSecret, $scope, $endpoint = null, Browser $browser = null, LoggerInterface $logger = null)
    {
        $this->browser   = $browser ?: new Browser(new Curl());
        $this->appId     = $appId;
        $this->appSecret = $appSecret;
        $this->scope     = $scope;
        $this->endpoint  = $endpoint ?: self::ENDPOINT;
        $this->logger    = $logger ?: new NullLogger();
    }

    public function setStrictChecks($strictChecks)
    {
        $this->strictChecks = (bool) $strictChecks;
    }

    /**
     * getAuthorizationUri
     *
     * @param mixed $callbackUri
     */
    public function getAuthorizationUri($callbackUri)
    {
        $params = array(
            'client_id'     => $this->appId,
            'scope'         => $this->scope,
            'redirect_uri'  => $callbackUri,
            'response_type' => 'code',
        );

        $uri = sprintf('%s%s?%s', $this->endpoint, $this->paths['authorize'], http_build_query($params));

        return $uri;
    }

    /**
     * getAccessToken
     *
     * @param mixed $callbackUri
     * @param  $authorizationCode
     *
     * @return array
     */
    public function requestAccessToken($callbackUri, $authorizationCode)
    {
        $params = array(
            'client_id'     => $this->appId,
            'client_secret' => $this->appSecret,
            'code'          => $authorizationCode,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $callbackUri,
            'response_type' => 'code',
            'scope'         => $this->scope,
            'strict'        => $this->strictChecks,
        );

        $url = sprintf('%s%s', $this->endpoint, $this->paths['access_token']);

        $this->logger->info(sprintf("Requesting AccessToken to '%s'", $url));
        $this->logger->debug(sprintf("Sent params: %s", json_encode($params)));

        $response = $this->browser->submit($url, $params);

        $this->logger->debug(sprintf("Response of AccessToken: %s", $response));

        $content = $response->getContent();
        $response = json_decode($content, true);

        if (null === $response) {
            $this->logger->error('Received non-json response.');

            throw new OAuthException('provider', "Response content couldn't be converted to JSON.");
        }

        if (isset($response['error'])) {
            $this->logger->error('The OAuth2 provider responded with an error');

            $error = $response['error'];
            $message = $response['message'];

            throw new OAuthException($error, $message);
        }

        $token = $response['access_token'];
        $scope = $response['scope'];

        return array('access_token' => $token, 'scope' => $scope);
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

    public function getBrowser()
    {
        return $this->browser;
    }

    public function getLogger()
    {
        return $this->logger;
    }
}
