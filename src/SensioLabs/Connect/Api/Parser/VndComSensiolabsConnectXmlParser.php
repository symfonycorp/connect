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
use SensioLabs\Connect\Api\Model\Form;
use SensioLabs\Connect\Api\Model\Error;

/**
 * VndComSensiolabsConnectXmlParser
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class VndComSensiolabsConnectXmlParser implements ParserInterface
{
    protected $dom;
    protected $xpath;

    protected $queries = array(
        'indexes'          => './users | ./clubs | ./projects | ./badges',
        'indexes_elements' => './foaf:Person | ./foaf:Group | ./membership | ./doap:Project | ./badge | ./doap:developer'
    );

    public function getContentType()
    {
        return 'application/vnd.com.sensiolabs.connect+xml';
    }

    public function parse($xml)
    {
        $this->dom = new \DOMDocument();

        try {
            if (!$xml) {
                throw new \ErrorException('The xml is empty.');
            }
            if (!$this->dom->loadXML($xml)) {
                throw new \ErrorException('Could not transform this xml to a \DOMDocument instance.');
            };
        } catch (\ErrorException $e) {
            throw new ApiParserException(sprintf('%s Body: "%s"', $e->getMessage(), $xml));
        }
        $this->xpath = new \DOMXpath($this->dom);
        $nodes = $this->xpath->evaluate('/api');
        if (1 === $nodes->length) {
            return $this->doParse($nodes->item(0));
        }

        throw new ApiParserException(sprintf('Could not parse this xml document. Is this the right content-type? Body: "%s"', $xml));
    }

    protected function doParse(\DOMElement $element = null)
    {
        $nodes = $this->xpath->evaluate('./root', $element);
        if (1 === $nodes->length) {
            return $this->parseRoot($nodes->item(0));
        }

        $nodes = $this->xpath->evaluate($this->queries['indexes'], $element);
        if (1 === $nodes->length) {
            return $this->parseIndex($nodes->item(0));
        }

        $nodes = $this->xpath->query('./foaf:Person', $element);
        if (1 === $nodes->length) {
            return $this->parseFoafPerson($nodes->item(0));
        }

        $nodes = $this->xpath->evaluate('./doap:Project', $element);
        if (1 === $nodes->length) {
            return $this->parseDoapProject($nodes->item(0));
        }

        $nodes = $this->xpath->evaluate('./foaf:Group', $element);
        if (1 === $nodes->length) {
            return $this->parseFoafGroup($nodes->item(0));
        }

        $nodes = $this->xpath->evaluate('./badge', $element);
        if (1 === $nodes->length) {
            return $this->parseBadge($nodes->item(0));
        }

        $nodes = $this->xpath->evaluate('./error');
        if (1 === $nodes->length) {
            return $this->parseError($nodes->item(0));
        }
    }

    protected function parseRoot(\DOMElement $element)
    {
        $root = $this->getRootInstance();

        $projectsLink = $this->xpath->query('./atom:link[@rel="https://rels.connect.sensiolabs.com/projects"]', $element);
        if ($projectsLink->length) {
            $root->setProjectsUrl($projectsLink->item(0)->attributes->getNamedItem('href')->value);
        }

        $badgesLink = $this->xpath->query('./atom:link[@rel="https://rels.connect.sensiolabs.com/badges"]', $element);
        if ($badgesLink->length) {
            $root->setBadgesUrl($badgesLink->item(0)->attributes->getNamedItem('href')->value);
        }

        $clubsLink = $this->xpath->query('./atom:link[@rel="https://rels.connect.sensiolabs.com/clubs"]', $element);
        if ($clubsLink->length) {
            $root->setClubsUrl($clubsLink->item(0)->attributes->getNamedItem('href')->value);
        }

        $usersLink = $this->xpath->query('./atom:link[@rel="https://rels.connect.sensiolabs.com/users"]', $element);
        if ($usersLink->length) {
            $root->setUsersUrl($usersLink->item(0)->attributes->getNamedItem('href')->value);
        }

        $user = $this->xpath->query('./foaf:Person', $element);
        if (1 === $user->length) {
            $user = $this->parseFoafPerson($user->item(0));
            $root->setCurrentUser($user);
        }

        $nodeList = $this->xpath->query('./xhtml:form', $element);
        foreach ($nodeList as $node) {
            $this->parseForm($root, $node);
        }

        return $root;
    }

    protected function parseIndex(\DOMElement $element)
    {
        $index = new Index($this->getLinkToSelf($element));
        $index->setTotal($element->attributes->getNamedItem('total')->value);
        $index->setCount($element->attributes->getNamedItem('count')->value);
        $index->setIndex($element->attributes->getNamedItem('index')->value);
        $index->setLimit($element->attributes->getNamedItem('limit')->value);

        $index->setNextUrl($this->getLinkNodeHref('./atom:link[@rel="next"]', $element));
        $index->setPrevUrl($this->getLinkNodeHref('./atom:link[@rel="prev"]', $element));

        $items = $this->xpath->query($this->queries['indexes_elements'], $element);
        for ($i = 0; $i < $items->length; $i++) {
            $index->addItems($this->parseIndexElement($items->item($i)));
        }

        $nodeList = $this->xpath->query('./xhtml:form', $element);
        if (1 === $nodeList->length) {
            $this->parseForm($index, $nodeList->item(0));
        }

        return $index;
    }

    protected function parseIndexElement(\DOMElement $element)
    {
        if ('foaf:Person' === $element->tagName) {
            return $this->parseFoafPerson($element);
        }

        if ('foaf:Group' === $element->tagName) {
            return $this->parseFoafGroup($element);
        }

        if ('doap:Project' === $element->tagName) {
            return $this->parseDoapProject($element);
        }

        if ('membership' === $element->tagName) {
            return $this->parseMembership($element);
        }

        if ('badge' === $element->tagName) {
            return $this->parseBadge($element);
        }
    }

    protected function parseDoapProject(\DOMElement $element)
    {
        $project = $this->getProjectInstance($this->getLinkToSelf($element), $this->getLinkToAlternate($element));

        $project->setUuid($element->attributes->getNamedItem('id')->value);
        $project->setImage($this->getLinkNodeHref('./atom:link[@rel="foaf:depiction"]', $element));
        $project->setName($this->getNodeValue('./doap:name', $element));
        $project->setSlug($this->getNodeValue('./slug', $element));
        $project->setIsPrivate($this->getNodeValue('./is-private', $element));
        $project->setDescription($this->getNodeValue('./doap:description', $element));
        $project->setType($project->getTypeFromTextual($this->getNodeValue('./doap:category', $element)));
        $project->setUrl($this->getNodeValue('./doap:homepage', $element));
        $project->setRepositoryUrl($this->getNodeValue('./doap:Repository/doap:GitRepository/doap:location', $element));
        $project->setIsInternalGitRepositoryCreated($this->getNodeValue('./doap:Repository/doap:GitRepository/doap:isInternalGitRepositoryCreated', $element));

        $nodeList = $this->xpath->query('./xhtml:form', $element);
        for ($i = 0; $i < $nodeList->length; $i++) {
            $this->parseForm($project, $nodeList->item($i));
        }

        return $project;
    }

    protected function parseFoafGroup(\DOMElement $element)
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
        $club->setCumulatedBadges($this->getNodeValue('./cumulated-badges', $element));

        $nodes = $this->xpath->query('./members/foaf:Person', $element);
        for ($i = 0; $i < $nodes->length; $i++) {
            $club->addMembers($this->parseFoafMember($nodes->item($i)));
        }

        $nodeList = $this->xpath->query('./badges', $element);
        if (1 === $nodeList->length) {
            $badges = $this->parseIndex($nodeList->item(0));
            $club->setBadges($badges);
        }

        $nodeList = $this->xpath->query('./xhtml:form', $element);
        if (1 === $nodeList->length) {
            $this->parseForm($club, $nodeList->item(0));
        }

        return $club;
    }

    protected function parseFoafMember(\DOMElement $element)
    {
        $member = new Member();

        $member->setIsMembershipPublic($this->getNodeValue('./is-membership-public', $element));
        $member->setIsOwner($this->getNodeValue('./is-owner', $element));

        $user = $this->parseFoafPerson($this->xpath->query('./foaf:Person', $element)->item(0));
        $member->setUser($user);

        return $member;
    }

    protected function parseFoafPerson(\DOMElement $element)
    {
        $user = $this->getUserInstance($this->getLinkToSelf($element), $this->getLinkToAlternate($element));
        $user->setUuid($element->attributes->getNamedItem('id')->value);
        $user->setName($this->getNodeValue('./foaf:name', $element));
        $user->setImage($this->getLinkToFoafDepiction($element));
        $user->setBiography($this->getNodeValue('./bio:olb', $element));
        $user->setBirthdate($this->getNodeValue('./foaf:birthday', $element));
        $user->setCity($this->getNodeValue('./vcard:locality', $element));
        $user->setCountry($this->getNodeValue('./vcard:country-name', $element));
        $user->setCompany($this->getNodeValue('./cv:hasWorkHistory/cv:employedIn', $element));
        $user->setJobPosition($this->getNodeValue('./cv:hasWorkHistory/cv:jobTitle', $element));
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

        $nodeList = $this->xpath->query('./badges', $element);
        if (1 === $nodeList->length) {
            $badges = $this->parseIndex($nodeList->item(0));
            $user->setBadges($badges);
        }

        $nodeList = $this->xpath->query('./memberships', $element);
        if (1 === $nodeList->length) {
            $memberships = $this->parseIndex($nodeList->item(0));
            $user->setMemberships($memberships);
        }

        $nodeList = $this->xpath->query('./projects', $element);
        if (1 === $nodeList->length) {
            $projects = $this->parseIndex($nodeList->item(0));
            $user->setProjects($projects);
        }

        $nodeList = $this->xpath->query('./xhtml:form', $element);
        foreach ($nodeList as $node) {
            $this->parseForm($user, $node);
        }

        return $user;
    }

    protected function parseForm(AbstractEntity $entity, \DOMElement $formElement)
    {
        $formId = $formElement->attributes->getNamedItem('id')->value;
        $formAction = $formElement->attributes->getNamedItem('action')->value;
        $formMethod = $formElement->attributes->getNamedItem('method')->value;
        $form = new Form($formAction, $formMethod);

        foreach ($this->parseFormFields($formElement) as $key => $value) {
            $form->addField($key, $value);
        }

        foreach ($this->parseFormSelect($formElement) as $field => $options) {
            $form->setFieldOptions($field, $options);
        }

        $entity->addForm($formId, $form);

        return $entity;
    }

    protected function parseFormSelect(\DOMElement $element)
    {
        $fieldsOptions = array();
        $nodeList = $this->xpath->query('./xhtml:select', $element);
        for ($i = 0; $i < $nodeList->length; $i++) {
            $node = $nodeList->item($i);
            $name = $node->attributes->getNamedItem('name')->value;
            $fieldsOptions[$name] = array();
            $optionList = $this->xpath->query('./xhtml:option', $node);
            for ($j = 0; $j < $optionList->length; $j++) {
                $option = $optionList->item($j);
                $value = $option->attributes->getNamedItem('value')->value;
                if ('' === $value) {
                    continue;
                }
                $fieldsOptions[$name][$value] = $option->nodeValue;
            }
        }

        return $fieldsOptions;
    }

    protected function parseFormFields(\DOMElement $element)
    {
        $fields = array();
        $nodeList = $this->xpath->query('./xhtml:input | ./xhtml:textarea | ./xhtml:select | ./xhtml:fieldset', $element);
        for ($i = 0; $i < $nodeList->length; $i++) {
            $node = $nodeList->item($i);

            if ('xhtml:fieldset' === $node->tagName) {
                $name = $node->attributes->getNamedItem('id')->value;
                $value = $this->parseFormFields($node);
            } else {
                $name = $node->attributes->getNamedItem('name')->value;
                $result = preg_match('/(.+)\[(.+)\]\[(.+)\]/', $name, $matches);
                if (1 === $result) {
                    $name = $matches[3];
                }

                if ('xhtml:input' === $node->tagName && 'checkbox' === $node->attributes->getNamedItem('type')->value) {
                    $value = (bool) $node->attributes->getNamedItem('checked');
                } else {
                    $value = $node->attributes->getNamedItem('value') ? $node->attributes->getNamedItem('value')->value : null;
                }
            }

            $fields[$name] = $value;
        }

        return $fields;
    }

    protected function parseMembership(\DOMElement $element)
    {
        $membership = new Membership();
        $nodeList = $this->xpath->query('./foaf:Group', $element);
        $membership->setClub($this->parseFoafGroup($nodeList->item(0)));
        $membership->setIsPublic($this->getNodeValue('./is-public', $element));
        $membership->setIsOwner($this->getNodeValue('./is-owner', $element));

        return $membership;
    }

    protected function parseBadge(\DOMElement $element)
    {
        $badge = new Badge($this->getLinkToSelf($element), $this->getLinkToAlternate($element));
        $badge->setId($element->attributes->getNamedItem('id')->value);
        $count = $element->attributes->getNamedItem('count');
        $badge->setCount($count ? $count->value : 1);
        $badge->setName($this->getNodeValue('./name', $element));
        $badge->setDescription($this->getNodeValue('./description', $element));
        $badge->setLevel($this->getNodeValue('./level', $element));
        $badge->setImage($this->getLinkToFoafDepiction($element));

        return $badge;
    }

    protected function parseError(\DOMElement $element)
    {
        $error = new Error();

        $parameters = $this->xpath->query('./entity/body/parameter', $element);
        foreach ($parameters as $parameter) {
            $name = $parameter->getAttribute('name');
            $error->addEntityBodyParameter($name);

            $messages = $this->xpath->query('./message', $parameter);
            foreach ($messages as $message) {
                $error->addEntityBodyParameterError($name, $this->sanitizeValue($message->nodeValue));
            }
        }

        return $error;
    }

    protected function getLinkToSelf(\DOMElement $element)
    {
        return $this->getLinkNodeHref('./atom:link[@rel="self"]', $element);
    }

    protected function getLinkToAlternate(\DOMElement $element)
    {
        return $this->getLinkNodeHref('./atom:link[@rel="self"]', $element, 1);
    }

    protected function getLinkToFoafDepiction(\DOMElement $element)
    {
        return $this->getLinkNodeHref('./atom:link[@rel="foaf:depiction"]', $element);
    }

    protected function getNodeValue($query, \DOMElement $element = null, $index = 0)
    {
        $nodeList = $this->xpath->query($query, $element);
        if ($nodeList->length > 0 && $index <= $nodeList->length) {
            return $this->sanitizeValue($nodeList->item($index)->nodeValue);
        }

        return;
    }

    protected function getLinkNodeHref($query, \DOMElement $element = null, $position = 0)
    {
        $nodeList = $this->xpath->query($query, $element);

        if ($nodeList && $nodeList->length > 0 && $nodeList->item($position)) {
            return $this->sanitizeValue($nodeList->item($position)->attributes->getNamedItem('href')->value);
        }

        return;
    }

    protected function sanitizeValue($value)
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

    protected function getRootInstance()
    {
        return new Root();
    }

    protected function getUserInstance($self, $alternate)
    {
        return new User($self, $alternate);
    }

    protected function getProjectInstance($self, $alternate)
    {
        return new Project($self, $alternate);
    }
}
