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

use SensioLabs\Connect\Api\Api;

/**
 * User.
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class User extends AbstractEntity
{
    public function configure()
    {
        $this->addProperty('username')
             ->addProperty('uuid')
             ->addProperty('name')
             ->addProperty('image')
             ->addProperty('biography')
             ->addProperty('birthdate')
             ->addProperty('city')
             ->addProperty('country')
             ->addProperty('company')
             ->addProperty('blogUrl')
             ->addProperty('feedUrl')
             ->addProperty('url')
             ->addProperty('email')
             ->addProperty('additionalEmails', array())
             ->addProperty('joinedAt')
             ->addProperty('githubUsername')
             ->addProperty('twitterUsername')
             ->addProperty('linkedInUrl')
             ->addProperty('facebookUid')
             ->addProperty('projects')
             ->addProperty('memberships')
             ->addProperty('badges')
        ;
    }

    public function setApi(Api $api)
    {
        parent::setApi($api);

        if ($this->getBadges()) {
            $this->getBadges()->setApi($api);
        }
    }

    public function getBirthday()
    {
        return $this->get('birthdate');
    }

    public function setBirthday($birthdate)
    {
        $this->set('birthdate', $birthdate);
    }
}

