<?php

namespace SensioLabs\Connect\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

if (method_exists(AbstractToken::class, '__serialize')) {
    /**
     * @internal
     */
    abstract class AbstractConnectToken extends AbstractToken
    {
        use SerializationConnectTokenTrait;

        private function setProviderKey($providerKey)
        {
            $reflection = new \ReflectionProperty(ConnectToken::class, 'providerKey');
            $reflection->setAccessible(true);
            $reflection->setValue($this, $providerKey);
        }
    }
} else {
    /**
     * @internal
     */
    abstract class AbstractConnectToken extends AbstractToken
    {
        public function serialize()
        {
            return serialize(array($this->getApiUser(), $this->getAccessToken(), $this->getProviderKey(), $this->getScope(), parent::serialize()));
        }

        public function unserialize($str)
        {
            list($apiUser, $accessToken, $providerKey, $scope, $parentStr) = unserialize($str);
            $this->setApiUser($apiUser);
            $this->setAccessToken($accessToken);
            $this->setProviderKey($providerKey);
            $this->setScope($scope);
            parent::unserialize($parentStr);
        }

        private function setProviderKey($providerKey)
        {
            $reflection = new \ReflectionProperty(ConnectToken::class, 'providerKey');
            $reflection->setAccessible(true);
            $reflection->setValue($this, $providerKey);
        }
    }
}
