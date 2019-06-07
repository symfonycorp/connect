<?php

namespace SensioLabs\Connect\Security\Authentication\Token;

trait SerializationConnectTokenTrait
{
    public function __serialize(): array
    {
        return array($this->apiUser, $this->accessToken, $this->providerKey, $this->scope, parent::__serialize());
    }

    public function __unserialize(array $data): void
    {
        list($this->apiUser, $this->accessToken, $this->providerKey, $this->scope) = $data;
        parent::__serialize($data);
    }
}
