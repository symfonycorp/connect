<?php

/*
 * This file is part of the SensioLabs Connect package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Connect\Security\Authentication\Token;

use SensioLabs\Connect\Api\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * ConnectToken
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class ConnectToken extends AbstractConnectToken
{
    private $accessToken;
    private $providerKey;
    private $apiUser;
    private $scope;

    public function __construct($user, $accessToken, User $apiUser = null, $providerKey, $scope = null, array $roles = array())
    {
        parent::__construct($roles);

        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->setUser($user);
        $this->setAccessToken($accessToken);
        $this->apiUser = $apiUser;
        $this->providerKey = $providerKey;
        $this->scope = $scope;

        parent::setAuthenticated(count($roles) > 0);
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

    public function getProviderKey()
    {
        return $this->providerKey;
    }

    public function getCredentials()
    {
        return $this->accessToken;
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
        if (!is_string($role) && !($role instanceof Role)) {
            throw new \InvalidArgumentException(sprintf('$roles must be an array of strings, or Role instances, but got %s.', gettype($role)));
        }

        return (string) $role;
    }

    private function getObjectUserRole($role)
    {
        if (is_string($role)) {
            return new Role($role);
        }

        if (!$role instanceof RoleInterface) {
            throw new \InvalidArgumentException(sprintf('$roles must be an array of strings, or RoleInterface instances, but got %s.', gettype($role)));
        }

        return $role;
    }
}
