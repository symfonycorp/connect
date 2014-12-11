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
use SensioLabs\Connect\Exception\ExceptionInterface;
use SensioLabs\Connect\Exception\OAuthException;
use SensioLabs\Connect\OAuthConsumer;
use SensioLabs\Connect\Security\Authentication\Token\ConnectToken;
use SensioLabs\Connect\Security\Exception\AuthenticationException;
use SensioLabs\Connect\Security\Exception\OAuthAccessDeniedException;
use SensioLabs\Connect\Security\Exception\OAuthStrictChecksFailedException;
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
    private $hideException = true;

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

    public function setRethrowException($rethrowException)
    {
        $this->hideException = !$rethrowException;
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
        } catch (ExceptionInterface $e) {
            if ($e instanceof OAuthException) {
                if ('access_denied' === $e->getType()) {
                    throw new OAuthAccessDeniedException($e);
                }
                if ('strict_condition' === $e->getType()) {
                    throw new OAuthStrictChecksFailedException($e);
                }
            }

            $this->logger and $this->logger->critical('Something went wrong while trying to access SensioLabsConnect.', array('exception' => $e));

            if ($this->hideException) {
                throw new AuthenticationException($e);
            }

            throw $e;
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
