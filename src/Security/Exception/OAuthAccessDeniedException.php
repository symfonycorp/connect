<?php

namespace SymfonyCorp\Connect\Security\Exception;

class OAuthAccessDeniedException extends AuthenticationException
{
    public function getMessageKey(): string
    {
        return 'You denied access to your SymfonyConnect account';
    }
}
