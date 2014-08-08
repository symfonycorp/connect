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

function createClient($endpoint, array $options = array()) {
    $options = array_replace_recursive(array(
        'plugins' => array(),
        'cache_options' => array(),
        'backoff_options' => array(
            'max_retries' => 3,
            'http_codes' => array(500, 503),
            'curl_codes' => null,
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

    return $client;
}
 