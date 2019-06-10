<?php

namespace SensioLabs\Connect\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

if (method_exists(AbstractToken::class, '__serialize')) {
    abstract class AbstractConnectToken extends AbstractToken
    {
        use SerializationConnectTokenTrait;
    }
} else {
    abstract class AbstractConnectToken extends AbstractToken
    {
        public function serialize()
        {
            return serialize(array($this->apiUser, $this->accessToken, $this->providerKey, $this->scope, parent::serialize()));
        }

        public function unserialize($str)
        {
            list($this->apiUser, $this->accessToken, $this->providerKey, $this->scope, $parentStr) = unserialize($str);
            
            parent::unserialize($parentStr);
        }
    }
}
