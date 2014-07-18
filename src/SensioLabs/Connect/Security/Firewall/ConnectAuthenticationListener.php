<?php

/*
 * This file is part of the SensioLabs Connect package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Connect\Security\Firewall;

use SensioLabs\Connect\Api\Api;
use SensioLabs\Connect\Exception\OAuthException;
use SensioLabs\Connect\OAuthConsumer;
use SensioLabs\Connect\Security\Authentication\Token\ConnectToken;
use SensioLabs\Connect\Security\Exception\AuthenticationException;
use SensioLabs\Connect\Security\Exception\OAuthAccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * ConnectAuthenticationListener.
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class ConnectAuthenticationListener extends AbstractAuthenticationListener
{
    private $oauthConsumer;
    private $api;
    private $oauthCallback = 'login_check';

    public function setOAuthConsumer(OAuthConsumer $oauthConsumer)
    {
        $this->oauthConsumer = $oauthConsumer;
    }

    public function setOAuthCallback($oauthCallback)
    {
        $this->oauthCallback = $oauthCallback;
    }

    public function setApi(Api $api)
    {
        $this->api = $api;
    }

    /**
     * {@inheritdoc}
     */
    protected function attemptAuthentication(Request $request)
    {
        try {
            if ($request->query->has('error')) {
                throw new OAuthException($request->query->get('error'), $request->query->get('error_description'));
            }

            if (!$request->query->has('code')) {
                throw new OAuthException('listener', 'No oauth code in the request.');
            }

            $data = $this->oauthConsumer->requestAccessToken($this->httpUtils->generateUri($request, $this->oauthCallback), $request->query->get('code'));
            $this->api->setAccessToken($data['access_token']);
            $apiUser = $this->api->getRoot()->getCurrentUser();
        } catch (\Exception $e) {
            if ($e instanceof OAuthException && 'access_denied' === $e->getType()) {
                throw new OAuthAccessDeniedException($e);
            }

            throw new AuthenticationException($e);
        }

        $flashBag = $request->getSession()->getFlashBag();
        if ($flashBag->has('sensiolabs_connect.oauth.target_path')) {
            $messages = $flashBag->get('sensiolabs_connect.oauth.target_path');
            $request->attributes->set('_target_path', $messages[0]);
        }

        $token = new ConnectToken($apiUser->getUuid(), $data['access_token'], $apiUser, $this->providerKey, $data['scope']);

        return $this->authenticationManager->authenticate($token);
    }
}
