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

use Guzzle\Http\Client as Guzzle;
use Guzzle\Common\Exception\GuzzleException;
use Guzzle\Http\Client;
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

    private $client;
    private $appId;
    private $appSecret;
    private $scope;
    private $endpoint;
    private $logger;

    protected $paths = array(
        'access_token'   => '/oauth/access_token',
        'authorize'      => '/oauth/authorize',
    );

    /**
     * @param string            $appId     The application Id
     * @param string            $appSecret The application secret
     * @param string            $scope     The application scope
     * @param string            $endpoint  The oauth endpoint
     * @param null|array|Guzzle $client    Either a Guzzle client or an array of options
     * @param LoggerInterface   $logger    A logger
     */
    public function __construct($appId, $appSecret, $scope, $endpoint = null, $client = null, LoggerInterface $logger = null)
    {
        if ($client instanceof Guzzle) {
            $this->client = $client;
        } elseif (is_array($client)) {
            $this->client = \SensioLabs\Connect\createClient(self::ENDPOINT, $client);
        } else {
            $this->client = \SensioLabs\Connect\createClient(self::ENDPOINT, array());
        }

        $this->appId     = $appId;
        $this->appSecret = $appSecret;
        $this->scope     = $scope;
        $this->endpoint  = $endpoint ?: self::ENDPOINT;
        $this->logger    = $logger ?: new NullLogger();
    }

    /**
     * getAuthorizationUri
     *
     * @param mixed $callbackUri
     *
     * @return string
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
     *
     * @throws OAuthException
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
        );

        $url = $this->paths['access_token'];

        $this->logger->info(sprintf("Requesting AccessToken to '%s'", $url));
        $this->logger->debug(sprintf("Sent params: %s", json_encode($params)));

        $request = $this->client->post($url, $params);
        $request->addPostFields($params);
        $response = $request->send();

        $this->logger->debug(sprintf("Response of AccessToken: %s", $response));

        try {
            $response = $response->json();
        } catch (GuzzleException $e) {
            $this->logger->error('Received non-json response.');
            $this->logger->debug($response->getBody(true));
            throw new OAuthException('provider', "Response content couldn't be converted to JSON.", $e);
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

    /**
     * @deprecated Deprecated since 4.0, use getClient instead
     *
     * @return Guzzle
     */
    public function getBrowser()
    {
        return $this->getClient();
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getLogger()
    {
        return $this->logger;
    }
}
