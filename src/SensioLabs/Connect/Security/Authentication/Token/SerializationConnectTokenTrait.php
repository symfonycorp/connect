<?php

namespace SensioLabs\Connect\Security\Authentication\Token;

/**
 * @internal
 */
trait SerializationConnectTokenTrait
{
    public function __serialize(): array
    {
        return array($this->getApiUser(), $this->getAccessToken(), $this->getProviderKey(), $this->getScope(), parent::__serialize());
    }

    public function __unserialize(array $data): void
    {
        list($apiUser, $accessToken, $providerKey, $scope) = $data;
        $this->setApiUser($apiUser);
        $this->setAccessToken($accessToken);
        $this->setProviderKey($providerKey);
        $this->setScope($scope);
        parent::__serialize($data);
    }
}
