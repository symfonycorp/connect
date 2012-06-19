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

use SensioLabs\Connect\Api\Exception\ApiException;
use SensioLabs\Connect\Api\Api;

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
        $response = $this->getApi()->get($this->getProjectsUrl());

        return $response['entity'];
    }

    public function searchProjects($q)
    {
        $form = $this->getForm('search_projects');
        $form->addField('q', $q);
        $response = $this->submit('search_projects');

        return $response['entity'];
    }

    public function getProject($uuid)
    {
        $response = $this->getApi()->get($this->getProjectsUrl().'/'.$uuid);

        return $response['entity'];
    }

    public function getBadges()
    {
        $response = $this->getApi()->get($this->getBadgesUrl());

        return $response['entity'];
    }

    public function getBadge($uuid)
    {
        $response = $this->getApi()->get($this->getBadgesUrl().'/'.$uuid);

        return $response['entity'];
    }

    public function getLastUsers()
    {
        $response = $this->getApi()->get($this->getUsersUrl());

        return $response['entity'];
    }

    public function searchUsers($q)
    {
        $form = $this->getForm('search_users');
        $form->addField('q', $q); 
        $response = $this->submit('search_users');

        return $response['entity'];
    }

    public function getUser($uuid)
    {
        $response = $this->getApi()->get($this->getUsersUrl().'/'.$uuid);

        return $response['entity'];
    }

    public function getLastClubs()
    {
        $response = $this->getApi()->get($this->getClubsUrl());

        return $response['entity'];
    }

    public function searchClubs($q)
    {
        $form = $this->getForm('search_clubs');
        $form->addField('q', $q);
        $response = $this->submit('search_clubs');

        return $response['entity'];
    }

    public function getClub($uuid)
    {
        $response = $this->getApi()->get($this->getClubsUrl().'/'.$uuid);

        return $response['entity'];
    }

    public function setApi(Api $api)
    {
        parent::setApi($api);

        if ($this->getCurrentUser()) {
            $this->getCurrentUser()->setApi($api);
        }
    }
}

