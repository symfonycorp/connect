<?php

namespace SymfonyCorp\Connect\Security\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException as BaseAuthenticationException;
use SymfonyCorp\Connect\Exception\ExceptionInterface;

class AuthenticationException extends BaseAuthenticationException implements ExceptionInterface
{
    public function __construct(\Exception $previous)
    {
        parent::__construct((string) $previous, 0, $previous);
    }

    public function getMessageKey(): string
    {
        return 'Impossible to process authentication with SymfonyConnect';
    }
}
