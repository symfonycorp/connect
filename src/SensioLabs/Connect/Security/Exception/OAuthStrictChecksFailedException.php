<?php

namespace SensioLabs\Connect\Security\Exception;

class OAuthStrictChecksFailedException extends AuthenticationException
{
    public function getMessageKey()
    {
        return 'A check failed: %message%';
    }

    public function getMessageData()
    {
        return array(
            '%message%' => $this->getMessage(),
        );
    }
}
