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
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class User extends AbstractEntity
{
    protected function configure()
    {
        $this->addProperty('username')
             ->addProperty('uuid')
             ->addProperty('name')
             ->addProperty('image')
             ->addProperty('jobPosition')
             ->addProperty('biography')
             ->addProperty('birthdate')
             ->addProperty('city')
             ->addProperty('country')
             ->addProperty('company')
             ->addProperty('blogUrl')
             ->addProperty('feedUrl')
             ->addProperty('url')
             ->addProperty('email')
             ->addProperty('additionalEmails', [])
             ->addProperty('joinedAt')
             ->addProperty('githubUsername')
             ->addProperty('twitterUsername')
             ->addProperty('linkedInUrl')
             ->addProperty('facebookUid')
             ->addProperty('badges')
        ;
    }

    public function getBirthday()
    {
        return $this->get('birthdate');
    }

    public function setBirthday($birthdate)
    {
        $this->set('birthdate', $birthdate);

        return $this;
    }
}
