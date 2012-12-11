<?php

/*
 * This file is part of the SensioLabs Connect package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Connect\Api\Parser;

/**
 * ParserInterface
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
interface ParserInterface
{
    public function parse($xml);
    public function getContentType();
}
