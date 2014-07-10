<?php

namespace SensioLabs\Connect\Security\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException as BaseAuthenticationException;
use SensioLabs\Connect\Exception\OAuthException;
use SensioLabs\Connect\Exception\ExceptionInterface;

class AuthenticationException extends BaseAuthenticationException implements ExceptionInterface
{
    private $type;

    public function __construct(OAuthException $previous)
    {
        $this->type = $previous->getType();

        parent::__construct((string) $previous, 0, $previous);
    }

    public function getMessageKey()
    {
        if ('access_denied' === $this->type) {
            return 'You denied access to your SensioLabsConnect account';
        }

        return 'Impossible to process authentication with SensioLabsConnect';
    }

    public function serialize()
    {
        return serialize(array($this->type, parent::serialize()));
    }

    public function unserialize($str)
    {
        list($this->type,$parentStr) = unserialize($str);

        parent::unserialize($parentStr);
    }
}
