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
use SensioLabs\Connect\OAuthConsumer;
use SensioLabs\Connect\Security\Authentication\Token\ConnectToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use SensioLabs\Connect\Exception\OAuthException;
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
        if ($request->query->has('error')) {
            throw new OAuthException($request->query->get('error'), $request->query->get('error_description'));
        }

        if (!$request->query->has('code')) {
            // cannot be an AuthenticationException as it would put yourself into an infinite loop
            // should never happen though
            throw new OAuthException('listener', 'No oauth code in the request.');
        }

        $data = $this->oauthConsumer->requestAccessToken($this->httpUtils->generateUri($request, $this->oauthCallback), $request->query->get('code'));
        $apiUser = $this->api->getRoot($data['access_token'])->getCurrentUser();

        if ($request->getSession()->getFlashBag()->has('sensiolabs_connect.oauth.target_path')) {
            $request->attributes->set('_target_path', $request->getSession()->getFlash('sensiolabs_connect.oauth.target_path'));
        }

        $token = new ConnectToken($apiUser->getUuid(), $data['access_token'], $apiUser, $this->providerKey, $data['scope']);

        return $this->authenticationManager->authenticate($token);
    }
}
