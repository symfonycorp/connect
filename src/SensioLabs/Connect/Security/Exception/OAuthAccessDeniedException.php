<?php

namespace SensioLabs\Connect\Security\Exception;

class OAuthAccessDeniedException extends AuthenticationException
{
    public function getMessageKey()
    {
        return 'You denied access to your SensioLabsConnect account';
    }
}
