<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\Exception;

/**
 * OAuthException
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class OAuthException extends \RuntimeException implements ExceptionInterface
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
        $this->type = $type ?: 'unknow type';

        $message = $message ?: 'no message provided';

        $message = sprintf('%s (%s)', $message, $this->type);
        parent::__construct($message, 0, $previousException);
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     * @deprecated
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    public function __toString()
    {
        return $this->message;
    }
}
