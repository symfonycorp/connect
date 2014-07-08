<?php

/*
 * This file is part of the SensioLabs Connect package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Connect\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * OAuthException
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class OAuthException extends AuthenticationException implements ExceptionInterface
{
    private $type;

    /**
     * __construct
     *
     * @param string $type
     * @param string $message
     */
    public function __construct($type, $message, \Exception $previousException = null)
    {
        if (!$message) {
            $message = 'access_denied' === $type ? 'You denied the authorization' : 'Impossible to process authentication with SensioLabsConnect';
        }

        $this->type = $type;
        $this->message = $message;

        parent::__construct($message, 0, $previousException);
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function __toString()
    {
        return sprintf('%s (%s)', $this->message, $this->type);
    }
}
