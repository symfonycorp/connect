<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\HttpUtils;
use SymfonyCorp\Connect\Api\Api;
use SymfonyCorp\Connect\Exception\ExceptionInterface;
use SymfonyCorp\Connect\Exception\OAuthException;
use SymfonyCorp\Connect\OAuthConsumer;
use SymfonyCorp\Connect\Security\Authentication\Token\ConnectToken;
use SymfonyCorp\Connect\Security\Exception\OAuthAccessDeniedException;
use SymfonyCorp\Connect\Security\Exception\OAuthStrictChecksFailedException;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ConnectAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface, InteractiveAuthenticatorInterface
{
    private $oauthConsumer;
    private $api;
    private $userProvider;
    private $httpUtils;
    private $logger;
    private $oauthCallbackRoute;
    private $hideException = true;

    public function __construct(OAuthConsumer $oauthConsumer, Api $api, UserProviderInterface $userProvider, HttpUtils $httpUtils, LoggerInterface $logger = null, string $oauthCallbackRoute = 'symfony_connect_callback')
    {
        $this->oauthConsumer = $oauthConsumer;
        $this->api = $api;
        $this->userProvider = $userProvider;
        $this->httpUtils = $httpUtils;
        $this->logger = $logger;
        $this->oauthCallbackRoute = $oauthCallbackRoute;
    }

    public function setRethrowException(bool $rethrowException): void
    {
        $this->hideException = !$rethrowException;
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $session = $request->getSession();
        $session->start();

        if ($target = $request->query->get('target', $request->getRequestUri())) {
            $parsed = parse_url($target);
            if (!isset($parsed['host']) || $parsed['host'] === $request->getHttpHost()) {
                $session->getFlashBag()->set('symfony_connect.oauth.target_path', $target);
            }
        }

        $session->getFlashBag()->set('symfony_connect.oauth.state', $state = bin2hex(random_bytes(32)));

        return new RedirectResponse($this->oauthConsumer->getAuthorizationUri($this->httpUtils->generateUri($request, $this->oauthCallbackRoute), $state));
    }

    public function authenticate(Request $request): PassportInterface
    {
        $flashBag = $request->getSession()->getFlashBag();

        try {
            if ($request->query->has('error')) {
                throw new OAuthException($request->query->get('error', ''), $request->query->get('error_description', ''));
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

            $data = $this->oauthConsumer->requestAccessToken($this->httpUtils->generateUri($request, $this->oauthCallbackRoute), $request->query->get('code'));
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

        $localUser = $this->userProvider->loadUserByUsername($apiUser->getUuid());
        if (!$localUser instanceof UserInterface) {
            throw new AuthenticationServiceException('The user provider must return a UserInterface object.');
        }

        return new ConnectPassport($localUser, $apiUser, $data['access_token'], $data['scope']);
    }

    public function supports(Request $request): ?bool
    {
        return $this->httpUtils->checkRequestPath($request, $this->oauthCallbackRoute);
    }

    /**
     * @param ConnectPassport $passport
     */
    public function createAuthenticatedToken(PassportInterface $passport, string $firewallName): TokenInterface
    {
        return new ConnectToken($passport->getUser(), $passport->getAccessToken(), $passport->getApiUser(), $firewallName, $passport->getScope(), $passport->getUser()->getRoles());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->httpUtils->createRedirectResponse($request, $request->getSession()->getFlashBag()->get('symfony_connect.oauth.target_path')[0] ?? '/');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }

    public function isInteractive(): bool
    {
        return true;
    }
}
