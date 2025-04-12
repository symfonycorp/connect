<?php

namespace SymfonyCorp\Connect\Exception;

use SymfonyCorp\Connect\Api\Model\Error;

/**
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class ApiClientException extends ApiException
{
    private $error;

    public function __construct($statusCode, $body, $message, array $headers = [], ?Error $error = null, ?\Throwable $previous = null, $code = 0)
    {
        $this->error = $error;

        parent::__construct($statusCode, $body, $message, $headers, $previous, $code);
    }

    public function getError()
    {
        return $this->error;
    }
}
