<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\Security\Authentication\Provider;

use SymfonyCorp\Connect\Security\Authentication\Token\ConnectToken;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class ConnectAuthenticationProvider implements AuthenticationProviderInterface
{
    private $userProvider;
    private $providerKey;

    public function __construct(UserProviderInterface $userProvider, $providerKey)
    {
        $this->userProvider = $userProvider;
        $this->providerKey = $providerKey;
    }

    public function authenticate(TokenInterface $token)
    {
        try {
            $localUser = $this->userProvider->loadUserByUsername($token->getUser());

            $authorizedToken = new ConnectToken($localUser, $token->getAccessToken(), $token->getApiUser(), $this->providerKey, $token->getScope(), $localUser->getRoles());
            $authorizedToken->setAttributes($token->getAttributes());

            return $authorizedToken;
        } catch (\Exception $repositoryProblem) {
            $e = new AuthenticationServiceException($repositoryProblem->getMessage(), 0, $repositoryProblem);
            $e->setToken($token);

            throw $e;
        }
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof ConnectToken;
    }
}
