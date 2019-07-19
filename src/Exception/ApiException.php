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

class ApiException extends \RuntimeException implements ExceptionInterface
{
    protected $statusCode;
    protected $body;
    protected $headers = array();

    public function __construct($statusCode, $body, $message, array $headers = array(), \Exception $previous = null, $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->body = $body;
        $this->headers = array();

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getHeaders()
    {
        return $this->headers;
    }
}
