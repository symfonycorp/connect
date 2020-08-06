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
use SymfonyCorp\Connect\Security\EntryPoint\ConnectEntryPoint;

/**
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class OAuthController
{
    private $entryPoint;

    public function __construct(ConnectEntryPoint $entryPoint)
    {
        $this->entryPoint = $entryPoint;
    }

    public function newSessionAction(Request $request)
    {
        return $this->entryPoint->start($request);
    }
}
