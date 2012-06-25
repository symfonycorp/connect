<?php

/*
 * This file is part of the SensioLabs Connect package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Connect\Security\EntryPoint;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use SensioLabs\Connect\OAuthConsumer;
use Symfony\Component\Security\Http\HttpUtils;

class ConnectEntryPoint implements AuthenticationEntryPointInterface
{
    private $oauthConsumer;
    private $httpUtils;
    private $oauthCallback;

    public function __construct(OAuthConsumer $oauthConsumer, HttpUtils $httpUtils, $oauthCallback = 'login_check')
    {
        $this->oauthConsumer = $oauthConsumer;
        $this->httpUtils = $httpUtils;
        $this->oauthCallback = $oauthCallback;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        $request->getSession()->start();

        if ($request->query->has('target')) {
            $target = $request->query->get('target');
            $parsed = parse_url($target);
            if (!isset($parsed['host']) || $parsed['host'] !== $request->getHttpHost()) {
                $request->getSession()->setFlash('sensiolabs_connect.oauth.target_path', $target);
            }
        }

        return new RedirectResponse($this->oauthConsumer->getAuthorizationUri($this->httpUtils->generateUri($request, $this->oauthCallback)));
    }
}
