<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\Api\Parser;

/**
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
interface ParserInterface
{
    public function parse($xml);

    public function getContentType();
}
