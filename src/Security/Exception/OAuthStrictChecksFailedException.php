<?php

namespace SymfonyCorp\Connect\Security\Exception;

class OAuthStrictChecksFailedException extends AuthenticationException
{
    public function getMessageKey(): string
    {
        return 'A check failed: %message%';
    }

    public function getMessageData(): array
    {
        return [
            '%message%' => $this->getMessage(),
        ];
    }
}
