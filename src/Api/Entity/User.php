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

use DateTimeInterface;

/**
 * @method self              setUsername(string $username)
 * @method string|null       getUsername()
 * @method self              setUuid(string $uuid)
 * @method string|null       getUuid()
 * @method self              setName(?string $name)
 * @method string|null       getName()
 * @method self              setImage(?string $image)
 * @method string|null       getImage()
 * @method self              setJobPosition(?string $jobPosition)
 * @method string|null       getJobPosition()
 * @method self              setBiography(?string $bio)
 * @method string|null       getBiography()
 * @method self              setBirthdate(?string $date)
 * @method string|null       getBirthdate()
 * @method self              setCity(?string $city)
 * @method string|null       getCity()
 * @method self              setCountry(?string $country)
 * @method string|null       getCountry()
 * @method self              setCompany(?string $company)
 * @method string|null       getCompany()
 * @method self              setBlogUrl(?string $url)
 * @method string|null       getBlogUrl()
 * @method self              setFeedUrl(?string $url)
 * @method string|null       getFeedUrl()
 * @method self              setUrl(?string $url)
 * @method string|null       getUrl()
 * @method self              setEmail(string $email)
 * @method string            getEmail()
 * @method self              setAdditionalEmails(string[] $emails)
 * @method string[]          getAdditionalEmails()
 * @method self              setJoinedAt(DateTimeInterface $date)
 * @method DateTimeInterface getJoinedAt()
 * @method self              setGithubUsername(?string $username)
 * @method string|null       getGithubUsername()
 * @method self              setTwitterUsername(?string $username)
 * @method string|null       getTwitterUsername()
 * @method self              setBadges(Index $badges)
 * @method Index|null        getBadges()
 *
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
