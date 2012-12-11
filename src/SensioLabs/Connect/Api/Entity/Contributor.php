<?php

/*
 * This file is part of the SensioLabs Connect package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Connect\Api\Entity;

/**
 * Contributor
 *
 * @author Julien Galenski <julien.galenski@sensiolabs.com>
 */
class Contributor extends AbstractEntity
{
    public function configure()
    {
        $this->addProperty('user')
             ->addProperty('linesAdded')
             ->addProperty('linesDeleted')
             ->addProperty('commits')
             ->addProperty('rank')
        ;
    }
}
