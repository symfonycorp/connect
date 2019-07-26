<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\Security\Firewall;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use SymfonyCorp\Connect\Api\Api;
use SymfonyCorp\Connect\Exception\ExceptionInterface;
use SymfonyCorp\Connect\Exception\OAuthException;
use SymfonyCorp\Connect\OAuthConsumer;
use SymfonyCorp\Connect\Security\Authentication\Token\ConnectToken;
use SymfonyCorp\Connect\Security\Exception\AuthenticationException;
use SymfonyCorp\Connect\Security\Exception\OAuthAccessDeniedException;
use SymfonyCorp\Connect\Security\Exception\OAuthStrictChecksFailedException;

/**
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class ConnectAuthenticationListener extends AbstractAuthenticationListener
{
    private $oauthConsumer;
    private $api;
    private $oauthCallback = 'login_check';
    private $hideException = true;

    public function setOAuthConsumer(OAuthConsumer $oauthConsumer): void
    {
        $this->oauthConsumer = $oauthConsumer;
    }

    public function setOAuthCallback(string $oauthCallback): void
    {
        $this->oauthCallback = $oauthCallback;
    }

    public function setApi(Api $api): void
    {
        $this->api = $api;
    }

    public function setRethrowException(bool $rethrowException): void
    {
        $this->hideException = !$rethrowException;
    }

    /**
     * {@inheritdoc}
     */
    protected function attemptAuthentication(Request $request): TokenInterface
    {
        $flashBag = $request->getSession()->getFlashBag();

        try {
            if ($request->query->has('error')) {
                throw new OAuthException($request->query->get('error'), $request->query->get('error_description'));
            }

            if (!$request->query->has('code')) {
                throw new OAuthException('listener', 'No oauth code in the request.');
            }

            if (!$flashBag->has('symfony_connect.oauth.state')) {
                throw new OAuthException('listener', 'No state code in session.');
            }

            if ($request->query->get('state') !== $flashBag->get('symfony_connect.oauth.state')[0]) {
                throw new OAuthException('access_denied', 'Your session has expired. Please try again.');
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

            if (null !== $this->logger) {
                $this->logger->critical('Something went wrong while trying to access SymfonyConnect.', ['exception' => $e]);
            }

            if ($this->hideException) {
                throw new AuthenticationException($e);
            }

            throw $e;
        }

        if ($flashBag->has('symfony_connect.oauth.target_path')) {
            $messages = $flashBag->get('symfony_connect.oauth.target_path');
            $request->attributes->set('_target_path', $messages[0]);
        }

        $token = new ConnectToken($apiUser->getUuid(), $data['access_token'], $apiUser, $this->providerKey, $data['scope']);

        return $this->authenticationManager->authenticate($token);
    }
}
