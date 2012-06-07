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
 * Member
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class Member extends AbstractEntity
{
    public function configure()
    {
        $this->addProperty('user')
             ->addProperty('isMembershipPublic')
             ->addProperty('isOwner')
    }
}

