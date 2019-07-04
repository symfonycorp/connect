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
 * Root.
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class Root extends AbstractEntity
{
    protected function configure()
    {
        $this->addProperty('badgesUrl')
            ->addProperty('usersUrl')
            ->addProperty('currentUser')
        ;
    }

    public function getBadges()
    {
        return $this->getApi()->get($this->getBadgesUrl());
    }

    public function getBadge($uuid)
    {
        return $this->getApi()->get($this->getBadgesUrl().'/'.$uuid);
    }

    public function getLastUsers()
    {
        return $this->getApi()->get($this->getUsersUrl());
    }

    public function searchUsers($q)
    {
        $form = $this->getForm('search_users');
        $form->addField('q', $q);

        return $this->submit('search_users');
    }

    public function getUser($uuid)
    {
        return $this->getApi()->get($this->getUsersUrl().'/'.$uuid);
    }
}
