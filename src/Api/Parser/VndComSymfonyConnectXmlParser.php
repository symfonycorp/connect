<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\Api\Parser;

use SymfonyCorp\Connect\Api\Entity\AbstractEntity;
use SymfonyCorp\Connect\Api\Entity\Badge;
use SymfonyCorp\Connect\Api\Entity\Index;
use SymfonyCorp\Connect\Api\Entity\Root;
use SymfonyCorp\Connect\Api\Entity\User;
use SymfonyCorp\Connect\Api\Model\Error;
use SymfonyCorp\Connect\Api\Model\Form;
use SymfonyCorp\Connect\Exception\ApiParserException;

/**
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class VndComSymfonyConnectXmlParser implements ParserInterface
{
    protected $dom;
    protected $xpath;

    protected $queries = [
        'indexes' => './users | ./badges',
        'indexes_elements' => './foaf:Person | ./badge | ./doap:developer',
    ];

    public function getContentType()
    {
        return 'application/vnd.com.symfony.connect+xml';
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
            }
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

    protected function doParse(?\DOMElement $element = null)
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

        $nodes = $this->xpath->evaluate('./badge', $element);
        if (1 === $nodes->length) {
            return $this->parseBadge($nodes->item(0));
        }

        $nodes = $this->xpath->evaluate('./error');
        if (1 === $nodes->length) {
            return $this->parseError($nodes->item(0));
        }
    }

    protected function parseRoot(\DOMElement $element): Root
    {
        $root = $this->getRootInstance();

        $badgesLink = $this->xpath->query('./atom:link[@rel="https://rels.connect.symfony.com/badges"]', $element);
        if ($badgesLink->length) {
            $root->setBadgesUrl($badgesLink->item(0)->attributes->getNamedItem('href')->value);
        }

        $usersLink = $this->xpath->query('./atom:link[@rel="https://rels.connect.symfony.com/users"]', $element);
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

    protected function parseIndex(\DOMElement $element): Index
    {
        $index = new Index($this->getLinkToSelf($element));
        $index->setTotal($element->attributes->getNamedItem('total')->value);
        $index->setCount($element->attributes->getNamedItem('count')->value);
        $index->setIndex($element->attributes->getNamedItem('index')->value);
        $index->setLimit($element->attributes->getNamedItem('limit')->value);

        $index->setNextUrl($this->getLinkNodeHref('./atom:link[@rel="next"]', $element));
        $index->setPrevUrl($this->getLinkNodeHref('./atom:link[@rel="prev"]', $element));

        $items = $this->xpath->query($this->queries['indexes_elements'], $element);
        for ($i = 0; $i < $items->length; ++$i) {
            $index->addItems($this->parseIndexElement($items->item($i)));
        }

        $nodeList = $this->xpath->query('./xhtml:form', $element);
        if (1 === $nodeList->length) {
            $this->parseForm($index, $nodeList->item(0));
        }

        return $index;
    }

    protected function parseIndexElement(\DOMElement $element): ?AbstractEntity
    {
        if ('foaf:Person' === $element->tagName) {
            return $this->parseFoafPerson($element);
        }

        if ('badge' === $element->tagName) {
            return $this->parseBadge($element);
        }

        return null;
    }

    protected function parseFoafPerson(\DOMElement $element): User
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
        for ($i = 0; $i < $accounts->length; ++$i) {
            $account = $accounts->item($i);
            switch ($accountName = $this->getNodeValue('./foaf:name', $account)) {
                case 'SymfonyConnect':
                    $user->setUsername($this->getNodeValue('foaf:accountName', $account));
                    $user->setJoinedAt(new \DateTime($this->getNodeValue('./since', $account)));
                    break;
                case 'github':
                    $user->setGithubUsername($this->getNodeValue('./foaf:accountName', $account));
                    break;
                case 'twitter':
                    $user->setTwitterUsername($this->getNodeValue('./foaf:accountName', $account));
                    break;
                default:
                    throw new ApiParserException(sprintf('I do not know how to parse these kinds of OnlineAccount: %s', $accountName));
            }
        }

        $nodeList = $this->xpath->query('./foaf:mbox[@rel="alternate"]', $element);
        $additionalEmails = [];
        for ($i = 0; $i < $nodeList->length; ++$i) {
            $additionalEmails[] = $this->sanitizeValue($nodeList->item($i)->nodeValue);
        }
        $user->setAdditionalEmails($additionalEmails);

        $nodeList = $this->xpath->query('./badges', $element);
        if (1 === $nodeList->length) {
            $badges = $this->parseIndex($nodeList->item(0));
            $user->setBadges($badges);
        }

        $nodeList = $this->xpath->query('./xhtml:form', $element);
        foreach ($nodeList as $node) {
            $this->parseForm($user, $node);
        }

        return $user;
    }

    protected function parseForm(AbstractEntity $entity, \DOMElement $formElement): AbstractEntity
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

    protected function parseFormSelect(\DOMElement $element): array
    {
        $fieldsOptions = [];
        $nodeList = $this->xpath->query('./xhtml:select', $element);
        for ($i = 0; $i < $nodeList->length; ++$i) {
            $node = $nodeList->item($i);
            $name = $node->attributes->getNamedItem('name')->value;
            $fieldsOptions[$name] = [];
            $optionList = $this->xpath->query('./xhtml:option', $node);
            for ($j = 0; $j < $optionList->length; ++$j) {
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

    protected function parseFormFields(\DOMElement $element): array
    {
        $fields = [];
        $nodeList = $this->xpath->query('./xhtml:input | ./xhtml:textarea | ./xhtml:select | ./xhtml:fieldset', $element);
        for ($i = 0; $i < $nodeList->length; ++$i) {
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

    protected function parseBadge(\DOMElement $element): Badge
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

    protected function parseError(\DOMElement $element): Error
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

    protected function getNodeValue($query, ?\DOMElement $element = null, $index = 0)
    {
        $nodeList = $this->xpath->query($query, $element);
        if ($nodeList->length > 0 && $index <= $nodeList->length) {
            return $this->sanitizeValue($nodeList->item($index)->nodeValue);
        }
    }

    protected function getLinkNodeHref($query, ?\DOMElement $element = null, $position = 0)
    {
        $nodeList = $this->xpath->query($query, $element);

        if ($nodeList && $nodeList->length > 0 && $nodeList->item($position)) {
            return $this->sanitizeValue($nodeList->item($position)->attributes->getNamedItem('href')->value);
        }
    }

    protected function sanitizeValue(string $value)
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

    protected function getRootInstance(): Root
    {
        return new Root();
    }

    protected function getUserInstance(string $self, string $alternate): User
    {
        return new User($self, $alternate);
    }
}
