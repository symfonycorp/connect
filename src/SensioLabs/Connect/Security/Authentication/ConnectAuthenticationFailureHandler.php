<?php

/*
 * This file is part of the SensioLabs Connect package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Connect\Security\Authentication;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * ConnectAuthenticationFailureHandler.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ConnectAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    private $logger;
    private $securityContext;

    public function __construct(SecurityContextInterface $securityContext, LoggerInterface $logger = null)
    {
        $this->securityContext = $securityContext;
        $this->logger = $logger;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if (null !== $this->logger) {
            $this->logger->info(sprintf('Authentication request failed: %s', $exception->getMessage()));
        }

        $this->securityContext->setToken(null);

        $request->getSession()->set(SecurityContextInterface::AUTHENTICATION_ERROR, $exception);

        throw $exception;
    }
}
