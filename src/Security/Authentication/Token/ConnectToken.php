<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCorp\Connect\Api\Entity\User;

/**
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class ConnectToken extends AbstractToken
{
    private $accessToken;
    private $firewallName;
    private $apiUser;
    private $scope;

    public function __construct($user, $accessToken, ?User $apiUser = null, $firewallName, ?string $scope = null, array $roles = [])
    {
        parent::__construct($roles);

        if (empty($firewallName)) {
            throw new \InvalidArgumentException('$firewallName must not be empty.');
        }

        $this->setUser($user);
        $this->setAccessToken($accessToken);
        $this->apiUser = $apiUser;
        $this->firewallName = $firewallName;
        $this->scope = $scope;

        // @deprecated since Symfony 5.4
        if (method_exists($this, 'setAuthenticated')) {
            parent::setAuthenticated(\count($roles) > 0, false);
        }
    }

    public function getRoleNames(): array
    {
        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            return $user->getRoles();
        }

        return parent::getRoleNames();
    }

    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setApiUser($apiUser)
    {
        $this->apiUser = $apiUser;
    }

    public function getApiUser()
    {
        return $this->apiUser;
    }

    public function getFirewallName()
    {
        return $this->firewallName;
    }

    /**
     * @deprecated since Symfony 5.4
     */
    public function getCredentials(): mixed
    {
        return $this->accessToken;
    }

    public function __serialize(): array
    {
        return [$this->apiUser, $this->accessToken, $this->firewallName, $this->scope, parent::__serialize()];
    }

    public function __unserialize(array $data): void
    {
        [$this->apiUser, $this->accessToken, $this->firewallName, $this->scope, $parentState] = $data;

        parent::__unserialize($parentState);
    }
}
