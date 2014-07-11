<?php

namespace SensioLabs\Connect\Security\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException as BaseAuthenticationException;
use SensioLabs\Connect\Exception\OAuthException;
use SensioLabs\Connect\Exception\ExceptionInterface;

class AuthenticationException extends BaseAuthenticationException implements ExceptionInterface
{
    public function __construct(OAuthException $previous)
    {
        parent::__construct((string) $previous, 0, $previous);
    }

    public function getMessageKey()
    {
        return 'Impossible to process authentication with SensioLabsConnect';
    }
}
