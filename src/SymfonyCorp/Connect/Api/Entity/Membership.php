<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\Api\Entity;

/**
 * Membership
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class Membership extends AbstractEntity
{
    public function configure()
    {
        $this->addProperty('club')
             ->addProperty('isPublic')
             ->addProperty('isOwner')
        ;
    }
}
