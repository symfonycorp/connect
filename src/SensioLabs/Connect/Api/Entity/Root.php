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
 * Root.
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class Root extends AbstractEntity
{
    private $indexes = array();
    private $user;

    protected function configure()
    {
        $this->addProperty('projectsUrl')
             ->addProperty('badgesUrl')
             ->addProperty('clubsUrl')
             ->addProperty('usersUrl')
             ->addProperty('currentUser')
        ;
    }

    public function getLastProjects()
    {
        return $this->getApi()->get($this->getProjectsUrl());
    }

    public function searchProjects($q)
    {
        $form = $this->getForm('search_projects');
        $form->addField('q', $q);

        return $this->submit('search_projects');
    }

    public function getProject($uuid)
    {
        return $this->getApi()->get($this->getProjectsUrl().'/'.$uuid);
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

    public function getLastClubs()
    {
        return $this->getApi()->get($this->getClubsUrl());
    }

    public function searchClubs($q)
    {
        $form = $this->getForm('search_clubs');
        $form->addField('q', $q);

        return $this->submit('search_clubs');
    }

    public function getClub($uuid)
    {
        return $this->getApi()->get($this->getClubsUrl().'/'.$uuid);
    }
}
