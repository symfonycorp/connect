<?php

/*
 * This file is part of the SensioLabs Connect package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Connect\Api\Parser;

use SensioLabs\Connect\Exception\ApiParserException;
use SensioLabs\Connect\Api\Entity\AbstractEntity;
use SensioLabs\Connect\Api\Entity\Badge;
use SensioLabs\Connect\Api\Entity\Club;
use SensioLabs\Connect\Api\Entity\Project;
use SensioLabs\Connect\Api\Entity\Index;
use SensioLabs\Connect\Api\Entity\Member;
use SensioLabs\Connect\Api\Entity\Membership;
use SensioLabs\Connect\Api\Entity\Root;
use SensioLabs\Connect\Api\Entity\User;
use SensioLabs\Connect\Api\Entity\Contributor;

/**
 * VndComSensioLabsConnectXmlParser
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class VndComSensioLabsConnectXmlParser implements ParserInterface
{
    private $dom;
    private $xpath;

    public function getContentType()
    {
        return 'application/vnd.com.sensiolabs.connect+xml';
    }

    public function parse($xml)
    {
        $this->dom = new \DOMDocument();

        try {
            if (!$this->dom->loadXML($xml)) {
                throw new \ErrorException('Could not transform this xml to a \DOMDocument instance.');
            };
        } catch(\ErrorException $e) {
            throw new ApiParserException(sprintf('%s %s', $e->getMessage(), $xml));
        }
        $this->xpath = new \DOMXpath($this->dom);

        $nodes = $this->xpath->evaluate('/api/root');
        if (1 === $nodes->length) {
            return $this->parseRoot($nodes->item(0));
        }

        $nodes = $this->xpath->evaluate('/api/index');
        if (1 === $nodes->length) {
            return $this->parseIndex($nodes->item(0));
        }

        $nodes = $this->xpath->evaluate('/api/foaf:Person');
        if (1 === $nodes->length) {
            return $this->parseFoafPerson($nodes->item(0));
        }

        $nodes = $this->xpath->evaluate('/api/doap:Project');
        if (1 === $nodes->length) {
            return $this->parseDoapProject($nodes->item(0));
        }

        $nodes = $this->xpath->evaluate('/api/foaf:Group');
        if (1 === $nodes->length) {
            return $this->parseFoafGroup($nodes->item(0));
        }
    }

    private function parseRoot(\DOMElement $element)
    {
        $root = new Root();

        $links = $this->xpath->query('./atom:link', $element);
        for ($i = 0; $i < $links->length; $i++) {
            $attributes = $links->item($i)->attributes;

            $root->set($attributes->getNamedItem('title')->value.'Url', $attributes->getNamedItem('href')->value);
        }

        $user = $this->xpath->query('./foaf:Person', $element);
        if (1 === $user->length) {
            $user = $this->parseFoafPerson($user->item(0));
            $root->setCurrentUser($user);
        }

        return $root;
    }

    private function parseIndex(\DOMElement $element)
    {
        $index = new Index($this->getLinkToSelf($element));
        $index->setTotal($element->attributes->getNamedItem('total')->value);
        $index->setCount($element->attributes->getNamedItem('count')->value);
        $index->setIndex($element->attributes->getNamedItem('index')->value);
        $index->setLimit($element->attributes->getNamedItem('limit')->value);

        $index->setNextUrl($this->getLinkNodeHref('./atom:link[@rel="next"]', $element));
        $index->setPrevUrl($this->getLinkNodeHref('./atom:link[@rel="prev"]', $element));

        $items = $this->xpath->query('./foaf:Person | ./foaf:Group | ./membership | ./doap:Project | ./badge | ./doap:developer', $element);
        for ($i = 0; $i < $items->length; $i++) {
            $item = $items->item($i);
            $object = null;
            switch ($item->tagName) {
                case 'foaf:Person':
                    $object = $this->parseFoafPerson($item);
                    break;
                case 'foaf:Group':
                    $object = $this->parseFoafGroup($item);
                    break;
                case 'doap:Project':
                    $object = $this->parseDoapProject($item);
                    break;
                case 'membership':
                    $object = $this->parseMembership($item);
                    break;
                case 'badge':
                    $object = $this->parseBadge($item);
                    break;
                case 'doap:developer':
                    $object = $this->parseContributor($item);
                    break;
                default:
                    throw new ApiParserException(sprintf('I do not know how to parse %s tags', $item->tagName));
            }

            $index->addItems($object);
        }

        $nodeList = $this->xpath->query('./xhtml:form', $element);
        if (1 === $nodeList->length) {
            $this->parseForm($index, $nodeList->item(0));
        }

        return $index;
    }

    private function parseDoapProject(\DOMElement $element)
    {
        $project = new Project($this->getLinkToSelf($element), $this->getLinkToAlternate($element));

        $project->setUuid($element->attributes->getNamedItem('id')->value);
        $project->setImage($this->getLinkNodeHref('./atom:link[@rel="foaf:depiction"]', $element));
        $project->setName($this->getNodeValue('./doap:name', $element));
        $project->setIsPrivate($this->getNodeValue('./is-private', $element));
        $project->setSlug($this->getNodeValue('./slug', $element));
        $project->setDescription($this->getNodeValue('./doap:description', $element));
        $project->setType($project->getTypeFromTextual($this->getNodeValue('./doap:category', $element)));
        $project->setUrl($this->getNodeValue('./doap:homepage', $element));
        $project->setRepositoryUrl($this->getNodeValue('./doap:Repository/doap:GitRepository/doap:location', $element));

        $nodeList = $this->xpath->query('./xhtml:form', $element);
        if (1 === $nodeList->length) {
            $this->parseForm($project, $nodeList->item(0));
        }

        $nodeList = $this->xpath->query('./contributors/index', $element);
        if (1 === $nodeList->length) {
            $contributors = $this->parseIndex($nodeList->item(0));
            $project->setContributors($contributors);
        }

        return $project;
    }

    private function parseFoafGroup(\DOMElement $element)
    {
        $club = new Club($this->getLinkToSelf($element), $this->getLinkToAlternate($element));

        $club->setUuid($element->attributes->getNamedItem('id')->value);
        $club->setName($this->getNodeValue('./foaf:name', $element));
        $club->setDescription($this->getNodeValue('./description', $element));
        $club->setUrl($this->getNodeValue('./foaf:homepage', $element));
        $club->setSlug($this->getNodeValue('./slug', $element));
        $club->setEmail($this->getNodeValue('./email', $element));
        $club->setFeedUrl($this->getLinkNodeHref('./atom:link[@rel="related"]', $element));
        $club->setCity($this->getNodeValue('./vcard:locality', $element));
        $club->setCountry($this->getNodeValue('./vcard:country-name', $element));
        $club->setImage($this->getLinkNodeHref('./atom:link[@rel="foaf:depiction"]', $element));
        $club->setType($club->getTypeFromTextual($this->getNodeValue('./category', $element)));

        $nodes = $this->xpath->query('./members/foaf:Person', $element);
        for ($i = 0; $i < $nodes->length; $i++) {
            $club->addMembers($this->parseFoafMember($nodes->item($i)));
        }

        $nodeList = $this->xpath->query('./xhtml:form', $element);
        if (1 === $nodeList->length) {
            $this->parseForm($club, $nodeList->item(0));
        }

        return $club;
    }

    private function parseFoafMember(\DOMElement $element)
    {
        $member = new Member();

        $member->setIsMembershipPublic($this->getNodeValue('./is-membership-public', $element));
        $member->setIsOwner($this->getNodeValue('./is-owner', $element));

        $user = $this->parseFoafPerson($this->xpath->query('./foaf:Person', $element)->item(0));
        $member->setUser($user);

        return $member;
    }

    private function parseFoafPerson(\DOMElement $element)
    {
        $user = new User($this->getLinkToSelf($element), $this->getLinkToAlternate($element));
        $user->setUuid($element->attributes->getNamedItem('id')->value);
        $user->setName($this->getNodeValue('./foaf:name', $element));
        $user->setImage($this->getLinkToFoafDepiction($element));
        $user->setBiography($this->getNodeValue('./bio:olb', $element));
        $user->setBirthdate($this->getNodeValue('./foaf:birthday', $element));
        $user->setCity($this->getNodeValue('./vcard:locality', $element));
        $user->setCountry($this->getNodeValue('./vcard:country-name', $element));
        $user->setCompany($this->getNodeValue('./cv:hasWorkHistory/cv:employedIn', $element));
        $user->setBlogUrl($this->getNodeValue('./foaf:weblog', $element));
        $user->setUrl($this->getNodeValue('./foaf:homepage', $element));
        $user->setFeedUrl($this->getLinkNodeHref('./atom:link[@title="blog/feed"]', $element));
        $user->setEmail($this->getNodeValue('./foaf:mbox', $element));

        $accounts = $this->xpath->query('./foaf:account/foaf:OnlineAccount', $element);
        for ($i = 0; $i < $accounts->length; $i++) {
            $account = $accounts->item($i);
            switch ($accountName = $this->getNodeValue('./foaf:name', $account)) {
                case 'SensioLabs Connect':
                    $user->setUsername($this->getNodeValue('foaf:accountName', $account));
                    $user->setJoinedAt(new \DateTime($this->getNodeValue('./since', $account)));
                    break;
                case 'github':
                    $user->setGithubUsername($this->getNodeValue('./foaf:accountName', $account));
                    break;
                case 'facebook':
                    $user->setFacebookUid($this->getNodeValue('./foaf:accountName', $account));
                    break;
                case 'twitter':
                    $user->setTwitterUsername($this->getNodeValue('./foaf:accountName', $account));
                    break;
                case 'linkedin':
                    $user->setLinkedInUrl($this->getNodeValue('./foaf:accountName', $account));
                    break;
                default:
                    throw new ApiParserException(sprintf('I do not know how to parse these kinds of OnlineAccount: %s', $accountName));
            }
        }

        $nodeList = $this->xpath->query('./foaf:mbox[@rel="alternate"]', $element);
        $additionalEmails = array();
        for ($i = 0; $i < $nodeList->length; $i++) {
            $additionalEmails[] = $this->sanitizeValue($nodeList->item($i)->nodeValue);
        }
        $user->setAdditionalEmails($additionalEmails);

        $nodeList = $this->xpath->query('./badges/index', $element);
        if (1 === $nodeList->length) {
            $badges = $this->parseIndex($nodeList->item(0));
            $user->setBadges($badges);
        }

        $nodeList = $this->xpath->query('./memberships/index', $element);
        if (1 === $nodeList->length) {
            $memberships = $this->parseIndex($nodeList->item(0));
            $user->setMemberships($memberships);
        }

        $nodeList = $this->xpath->query('./projects/index', $element);
        if (1 === $nodeList->length) {
            $projects = $this->parseIndex($nodeList->item(0));
            $user->setProjects($projects);
        }

        $nodeList = $this->xpath->query('./xhtml:form', $element);
        if (1 === $nodeList->length) {
            $this->parseForm($user, $nodeList->item(0));
        }

        return $user;
    }

    private function parseForm(AbstractEntity $entity, \DOMElement $form)
    {
        $entity->setFormMethod($form->attributes->getNamedItem('method')->value);
        $entity->setFormAction($form->attributes->getNamedItem('action')->value);

        $nodeList = $this->xpath->query('./xhtml:input', $form);
        for ($i = 0; $i < $nodeList->length; $i++) {

            $node = $nodeList->item($i);

            $id = $node->attributes->getNamedItem('id')->value;
            $value = $node->attributes->getNamedItem('value');

            $entity->addFormField($id, $value ? $value->value : null);
        }

        return $entity;
    }

    private function parseMembership(\DOMElement $element)
    {
        $membership = new Membership();
        $nodeList = $this->xpath->query('./foaf:Group', $element);
        $membership->setClub($this->parseFoafGroup($nodeList->item(0)));
        $membership->setIsPublic($this->getNodeValue('./is-public', $element));
        $membership->setIsOwner($this->getNodeValue('./is-owner', $element));

        return $membership;
    }

    private function parseBadge(\DOMElement $element)
    {
        $badge = new Badge($this->getLinkToSelf($element), $this->getLinkToAlternate($element));
        $badge->setId($element->attributes->getNamedItem('id')->value);
        $badge->setName($this->getNodeValue('./name', $element));
        $badge->setDescription($this->getNodeValue('./description', $element));
        $badge->setLevel($this->getNodeValue('./level', $element));
        $badge->setImage($this->getLinkToFoafDepiction($element));

        return $badge;
    }

    public function parseContributor(\DOMElement $element)
    {
        $contributor = new Contributor();
        $contributor->setLinesAdded($this->getNodeValue('./lines-added', $element));
        $contributor->setLinesDeleted($this->getNodeValue('./lines-deleted', $element));
        $contributor->setCommits($this->getNodeValue('./commits', $element));
        $contributor->setRank($this->getNodeValue('./rank', $element));

        $nodeList = $this->xpath->query('./foaf:Person', $element);
        $userElement = $nodeList->item(0);
        $user = $this->parseFoafPerson($userElement);

        $contributor->setUser($user);

        return $contributor;

    }

    private function getLinkToSelf(\DOMElement $element)
    {
        return $this->getLinkNodeHref('./atom:link[@rel="self"]', $element);
    }

    private function getLinkToAlternate(\DOMElement $element)
    {
        return $this->getLinkNodeHref('./atom:link[@rel="alternate"]', $element);
    }

    private function getLinkToFoafDepiction(\DOMElement $element)
    {
        return $this->getLinkNodeHref('./atom:link[@rel="foaf:depiction"]', $element);
    }

    private function getNodeValue($query, \DOMElement $element = null, $index = 0)
    {
        $nodeList = $this->xpath->query($query, $element);
        if ($nodeList->length > 0 && $index <= $nodeList->length) {
            return $this->sanitizeValue($nodeList->item($index)->nodeValue);
        }

        return null;
    }

    private function getLinkNodeHref($query, \DOMElement $element = null)
    {
        $nodeList = $this->xpath->query($query, $element);

        if ($nodeList && $nodeList->length > 0) {
            return $this->sanitizeValue($nodeList->item(0)->attributes->getNamedItem('href')->value);
        }

        return null;
    }

    private function sanitizeValue($value)
    {
        if ('true' === $value) {
            $value = true;
        } elseif ('false' === $value) {
            $value = false;
        } elseif (empty($value)) {
            $value = null;
        }

        return $value;
    }
}
