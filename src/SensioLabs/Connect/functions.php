<?php

/*
 * This file is part of the SensioLabs Connect package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Connect;

use Guzzle\Http\Client as Guzzle;
use Guzzle\Plugin\Backoff\BackoffPlugin;
use Guzzle\Plugin\Cache\CachePlugin;

const USER_AGENT = 'SensioLabsConnect SDK -';
const CONNECT_TIMEOUT = 5;
const TRANSFERT_TIMEOUT = 5;

function createClient($endpoint, array $options = array())
{
    $options = array_replace_recursive(array(
        'plugins' => array(),
        'cache_options' => array(),
        'backoff_options' => array(
            'max_retries' => 3,
            'http_codes' => array(500, 503),
            'curl_codes' => null,
        ),
        'options' => array(
            'timeout' => TRANSFERT_TIMEOUT, // maximum number of seconds to allow for an entire transfer to take place before timing out
            'connect_timeout' => CONNECT_TIMEOUT, // maximum number of seconds to wait while trying to connect
        ),
    ), $options);

    if (false !== $options['backoff_options']) {
        $options['plugins'][] = BackoffPlugin::getExponentialBackoff($options['backoff_options']['max_retries'], $options['backoff_options']['http_codes'], $options['backoff_options']['curl_codes']);
    }

    if (false !== $options['cache_options']) {
        $options['plugins'][] = new CachePlugin($options['cache_options']);
    }

    $client = new Guzzle($endpoint);

    foreach ($options['plugins'] as $plugin) {
        $client->addSubscriber($plugin);
    }

    $client->setDefaultOption('timeout', $options['timeout']);
    $client->setDefaultOption('connect_timeout', $options['connect_timeout']);
    $client->setDefaultOption('exceptions', false);

    if (isset($options['proxy'])) {
        $client->setDefaultOption('proxy', $options['proxy']);
    }

    $client->setUserAgent(USER_AGENT, true);

    return $client;
}
