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
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
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
    private $hideException = true;
    private $startTemplate;
    private $failureTemplate;

    public function __construct(OAuthConsumer $oauthConsumer, Api $api, UserProviderInterface $userProvider, HttpUtils $httpUtils, LoggerInterface $logger = null, string $startTemplate = null, string $failureTemplate = null)
    {
        $this->oauthConsumer = $oauthConsumer;
        $this->api = $api;
        $this->userProvider = $userProvider;
        $this->httpUtils = $httpUtils;
        $this->logger = $logger;
        $this->startTemplate = $startTemplate;
        $this->failureTemplate = $failureTemplate;
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
        $authenticationUri = $this->oauthConsumer->getAuthorizationUri($this->httpUtils->generateUri($request, 'symfony_connect_callback'), $state);

        if (!$this->startTemplate) {
            return new RedirectResponse($authenticationUri);
        }

        $request->getSession()->set('symfony_connect.authentication_uri', $authenticationUri);

        return new RedirectResponse($this->httpUtils->generateUri($request, 'symfony_connect_start'));
    }

    public function authenticate(Request $request): Passport
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

            $data = $this->oauthConsumer->requestAccessToken($this->httpUtils->generateUri($request, 'symfony_connect_callback'), $request->query->get('code'));
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
                $this->logger->critical(sprintf('Something went wrong while trying to access SymfonyConnect: %s.', $e->getMessage()), ['exception' => $e]);
            }

            if ($this->hideException) {
                throw new AuthenticationException($e->getMessage(), 0, $e);
            }

            throw $e;
        }

        $localUser = method_exists($this->userProvider, 'loadUserByIdentifier') ? $this->userProvider->loadUserByIdentifier($apiUser->getUuid()) : $this->userProvider->loadUserByUsername($apiUser->getUuid());
        if (!$localUser instanceof UserInterface) {
            throw new AuthenticationServiceException('The user provider must return a UserInterface object.');
        }

        $passport = new SelfValidatingPassport(new UserBadge($apiUser->getUuid()), []);
        $passport->setAttribute('apiUser', $apiUser);
        $passport->setAttribute('accessToken', $data['access_token']);
        $passport->setAttribute('scope', $data['scope']);

        return $passport;
    }

    public function supports(Request $request): ?bool
    {
        return $this->httpUtils->checkRequestPath($request, 'symfony_connect_callback');
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        return new ConnectToken($passport->getUser(), $passport->getAttribute('accessToken'), $passport->getAttribute('apiUser'), $firewallName, $passport->getAttribute('scope'), $passport->getUser()->getRoles());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->httpUtils->createRedirectResponse($request, $request->getSession()->getFlashBag()->get('symfony_connect.oauth.target_path')[0] ?? '/');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if (!$this->failureTemplate) {
            return null;
        }

        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->httpUtils->generateUri($request, 'symfony_connect_failure'));
    }

    public function isInteractive(): bool
    {
        return true;
    }
}
