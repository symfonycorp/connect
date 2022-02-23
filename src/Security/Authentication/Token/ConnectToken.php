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
use Symfony\Component\Security\Core\Role\Role;
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

    public function __construct($user, $accessToken, User $apiUser = null, $firewallName, $scope = null, array $roles = [])
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
            return $this->getUserRoles($user);
        }

        return parent::getRoleNames();
    }

    public function getRoles()
    {
        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            return $this->getUserRoles($user);
        }

        if (method_exists(AbstractToken::class, 'getRoleNames')) {
            return parent::getRoleNames();
        }

        return parent::getRoles();
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

    /**
     * @deprecated use getFirewallName() instead
     */
    public function getProviderKey()
    {
        return $this->firewallName;
    }

    public function getFirewallName()
    {
        return $this->firewallName;
    }

    /**
     * @return mixed
     */
    public function getCredentials()
    {
        return $this->accessToken;
    }

    public function __serialize(): array
    {
        return [$this->apiUser, $this->accessToken, $this->firewallName, $this->scope, parent::__serialize()];
    }

    public function __unserialize(array $data): void
    {
        list($this->apiUser, $this->accessToken, $this->firewallName, $this->scope, $parentState) = $data;

        parent::__unserialize($parentState);
    }

    private function getUserRoles(UserInterface $user)
    {
        $callBackMethod = 'getObjectUserRole';

        if (method_exists(AbstractToken::class, 'getRoleNames')) {
            $callBackMethod = 'getStringUserRole';
        }

        return array_map([$this, $callBackMethod], $user->getRoles());
    }

    private function getStringUserRole($role)
    {
        if (!\is_string($role) && !($role instanceof Role)) {
            throw new \InvalidArgumentException(sprintf('$roles must be an array of strings, or Role instances, but got %s.', \gettype($role)));
        }

        return (string) $role;
    }

    private function getObjectUserRole($role)
    {
        if (\is_string($role)) {
            return new Role($role);
        }

        if (!$role instanceof RoleInterface) {
            throw new \InvalidArgumentException(sprintf('$roles must be an array of strings, or RoleInterface instances, but got %s.', \gettype($role)));
        }

        return $role;
    }
}
