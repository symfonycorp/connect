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
    protected function configure()
    {
        $this->addProperty('projectsUrl')
             ->addProperty('badgesUrl')
             ->addProperty('clubsUrl')
             ->addProperty('usersUrl')
             ->addProperty('currentUser')
        ;
    }

    /**
     * @deprecated since Connect 4.3 and will be removed in 5.0.
     */
    public function getLastProjects()
    {
        @trigger_error(sprintf('The "%s()" method is deprecated since Connect 4.3 and will be removed in 5.0.', __METHOD__), E_USER_DEPRECATED);

        return $this->getApi()->get($this->getProjectsUrl());
    }

    /**
     * @deprecated since Connect 4.3 and will be removed in 5.0.
     */
    public function searchProjects($q)
    {
        @trigger_error(sprintf('The "%s()" method is deprecated since Connect 4.3 and will be removed in 5.0.', __METHOD__), E_USER_DEPRECATED);

        $form = $this->getForm('search_projects');
        $form->addField('q', $q);

        return $this->submit('search_projects');
    }

    /**
     * @deprecated since Connect 4.3 and will be removed in 5.0.
     */
    public function getProject($uuid)
    {
        @trigger_error(sprintf('The "%s()" method is deprecated since Connect 4.3 and will be removed in 5.0.', __METHOD__), E_USER_DEPRECATED);

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

    /**
     * @deprecated since Connect 4.3 and will be removed in 5.0.
     */
    public function getLastClubs()
    {
        @trigger_error(sprintf('The "%s()" method is deprecated since Connect 4.3 and will be removed in 5.0.', __METHOD__), E_USER_DEPRECATED);

        return $this->getApi()->get($this->getClubsUrl());
    }

    /**
     * @deprecated since Connect 4.3 and will be removed in 5.0.
     */
    public function searchClubs($q)
    {
        @trigger_error(sprintf('The "%s()" method is deprecated since Connect 4.3 and will be removed in 5.0.', __METHOD__), E_USER_DEPRECATED);

        $form = $this->getForm('search_clubs');
        $form->addField('q', $q);

        return $this->submit('search_clubs');
    }

    /**
     * @deprecated since Connect 4.3 and will be removed in 5.0.
     */
    public function getClub($uuid)
    {
        @trigger_error(sprintf('The "%s()" method is deprecated since Connect 4.3 and will be removed in 5.0.', __METHOD__), E_USER_DEPRECATED);

        return $this->getApi()->get($this->getClubsUrl().'/'.$uuid);
    }
}
