<?php

/*
 * This file is part of the SensioLabs Connect package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Connect\Buzz\Client;

use Buzz\Client\Curl as BaseCurl;
use Buzz\Message;

/**
 * Curl
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class Curl extends BaseCurl
{
    static protected function setCurlOptsFromRequest($curl, Message\Request $request)
    {
        parent::setCurlOptsFromRequest($curl, $request);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    }
}

