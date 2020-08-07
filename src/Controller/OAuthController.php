<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use SymfonyCorp\Connect\Security\EntryPoint\ConnectEntryPoint;
use SymfonyCorp\Connect\Security\Exception\OAuthAccessDeniedException;
use SymfonyCorp\Connect\Security\Exception\OAuthStrictChecksFailedException;
use Twig\Environment;

/**
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class OAuthController
{
    private $entryPoint;
    private $startTemplate;
    private $failureTemplate;

    public function __construct(ConnectEntryPoint $entryPoint, string $startTemplate = null, string $failureTemplate = null)
    {
        $this->entryPoint = $entryPoint;
        $this->startTemplate = $startTemplate;
        $this->failureTemplate = $failureTemplate;
    }

    public function newSession(Request $request)
    {
        return $this->entryPoint->start($request);
    }

    public function start(Request $request, Environment $twig)
    {
        return new Response($twig->render($this->startTemplate, [
            'authentication_uri' => $request->getSession()->get('symfony_connect.authentication_uri'),
        ]));
    }

    public function failure(Request $request, Environment $twig)
    {
        $e = $request->getSession()->get(Security::AUTHENTICATION_ERROR);

        $type = '';
        if ($e instanceof OAuthStrictChecksFailedException) {
            $type = 'strict_condition';
        } elseif ($e instanceof OAuthAccessDeniedException) {
            $type = 'access_denied';
        }

        return new Response($twig->render($this->failureTemplate, [
            'type' => $type,
            'authentication_error' => $e,
        ]));
    }
}
