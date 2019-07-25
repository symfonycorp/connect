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
 * @method self setUsername(string $username)
 * @method null|string getUsername()
 * @method self setUuid(string $uuid)
 * @method null|string getUuid()
 * @method self setName(?string $name)
 * @method null|string getName()
 * @method self setImage(?string $image)
 * @method null|string getImage()
 * @method self setJobPosition(?string $jobPosition)
 * @method null|string getJobPosition()
 * @method self setBiography(?string $bio)
 * @method null|string getBiography()
 * @method self setBirthdate(?string $date)
 * @method null|string getBirthdate()
 * @method self setCity(?string $city)
 * @method null|string getCity()
 * @method self setCountry(?string $country)
 * @method null|string getCountry()
 * @method self setCompany(?string $company)
 * @method null|string getCompany()
 * @method self setBlogUrl(?string $url)
 * @method null|string getBlogUrl()
 * @method self setFeedUrl(?string $url)
 * @method null|string getFeedUrl()
 * @method self setUrl(?string $url)
 * @method null|string getUrl()
 * @method self setEmail(string $email)
 * @method string getEmail()
 * @method self setAdditionalEmails(string[] $emails)
 * @method string[] getAdditionalEmails()
 * @method self setJoinedAt(DateTimeInterface $date)
 * @method DateTimeInterface getJoinedAt()
 * @method self setGithubUsername(?string $username)
 * @method null|string getGithubUsername()
 * @method self setTwitterUsername(?string $username)
 * @method null|string getTwitterUsername()
 * @method self setLinkedInUrl(?string $url)
 * @method null|string getLinkedInUrl()
 * @method self setBadges(Index $badges)
 * @method null|Index getBadges()
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
             ->addProperty('linkedInUrl')
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
