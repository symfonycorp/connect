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
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;
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

    public function __construct(ConnectEntryPoint $entryPoint, ?string $startTemplate = null, ?string $failureTemplate = null)
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

    public function failure(Request $request, Environment $twig, ?TranslatorInterface $translator = null)
    {
        if (!$e = $request->getSession()->get(SecurityRequestAttributes::AUTHENTICATION_ERROR)) {
            // directly going to this controller without an exception should generate a 404
            return new Response('', 404);
        }

        $type = '';
        if ($e instanceof OAuthStrictChecksFailedException) {
            $type = 'strict_condition';
        } elseif ($e instanceof OAuthAccessDeniedException) {
            $type = 'access_denied';
        }

        if (null === $translator) {
            $translator = new class() implements TranslatorInterface {
                use TranslatorTrait;
            };
        }

        return new Response($twig->render($this->failureTemplate, [
            'type' => $type,
            'authentication_error' => $e,
            'authentication_error_message' => $translator->trans((string) $e->getMessageKey(), $e->getMessageData() ?: [], 'security'),
        ]));
    }
}
