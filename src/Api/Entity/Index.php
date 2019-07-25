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

use SymfonyCorp\Connect\Api\Api;

/**
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class Index extends AbstractEntity implements \Countable, \ArrayAccess, \IteratorAggregate
{
    protected function configure()
    {
        $this->addProperty('items', [])
             ->addProperty('total')
             ->addProperty('count')
             ->addProperty('index')
             ->addProperty('limit')
             ->addProperty('nextUrl')
             ->addProperty('prevUrl')
        ;
    }

    public function getNext()
    {
        if ($this->getNextUrl()) {
            $response = $this->getApi()->get($this->getNextUrl());

            return $response['entity'];
        }

        throw new \RuntimeException('I do not know how to get the next elements of this index.');
    }

    public function getPrev()
    {
        if ($this->getPrevUrl()) {
            $response = $this->getApi()->get($this->getPrevUrl());

            return $response['entity'];
        }

        throw new \RuntimeException('I do not know how to get the previous elements of this index.');
    }

    public function setApi(Api $api)
    {
        parent::setApi($api);

        foreach ($this->getItems() as $item) {
            if ($item instanceof AbstractEntity) {
                $item->setApi($api);
            }
        }
    }

    public function count()
    {
        return $this->getCount();
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->get('items'));
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->get('items'));
    }

    public function offsetGet($offset)
    {
        $items = $this->get('items');

        return $items[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException('Not implemented.');
    }

    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('Not implemented.');
    }
}
