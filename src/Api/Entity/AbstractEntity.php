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
use SymfonyCorp\Connect\Api\Model\Form;

/**
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
abstract class AbstractEntity implements \ArrayAccess, \Serializable
{
    private $selfUrl;
    private $alternateUrl;
    private $properties;
    private $forms;
    private $api;

    public function __construct($selfUrl = null, $alternateUrl = null)
    {
        $this->selfUrl = $selfUrl;
        $this->alternateUrl = $alternateUrl;
        $this->properties = [];
        $this->forms = [];
        $this->configure();
    }

    public function setApi(Api $api)
    {
        $this->api = $api;
        foreach ($this->properties as $property) {
            if ($property instanceof self) {
                $property->setApi($api);
            }
        }
    }

    public function getApi()
    {
        return $this->api;
    }

    public function refresh()
    {
        $fresh = $this->getApi()->get($this->selfUrl);
        foreach ($this->properties as $key => $property) {
            $this->set($key, $fresh->get($key));
        }

        $this->setForms($fresh->getForms());

        return $this;
    }

    public function __toString()
    {
        return $this->selfUrl;
    }

    public function addProperty($name, $default = null)
    {
        $this->properties[$name] = $default;

        return $this;
    }

    public function setForms($forms)
    {
        $this->forms = $forms;
    }

    public function addForm($formId, Form $form)
    {
        $this->forms[$formId] = $form;
    }

    public function getForm($formId)
    {
        return $this->forms[$formId];
    }

    public function getForms()
    {
        return $this->forms;
    }

    public function submit($formId, self $entity = null)
    {
        $form = $this->forms[$formId];
        $fields = $form->getFields();

        if (null === $entity) {
            $entity = $this;
        }

        foreach ($fields as $key => $value) {
            if (!$entity->has($key)) {
                continue;
            }
            if (!\is_array($fields[$key])) {
                $fields[$key] = $entity->get($key);
            } else {
                // we have a collection of fields that should be repeated.
                $template = $fields[$key];
                $fields[$key] = [];
                foreach ($entity->get($key) as $subEntity) {
                    $subFields = [];
                    foreach ($template as $k => $v) {
                        if (!$subEntity->has($k)) {
                            continue;
                        }

                        $subFields[$k] = $subEntity->get($k);
                    }

                    $fields[$key][] = $subFields;
                }
            }
        }

        return $this->api->submit($form->getAction(), $form->getMethod(), $fields);
    }

    public function __call($name, $arguments)
    {
        $method = substr($name, 0, 3);
        $property = lcfirst(substr($name, 3));

        if ('set' === $method) {
            if (!\array_key_exists(0, $arguments)) {
                throw new \LogicException(sprintf('Please provide a value to set %s with', $property));
            }

            return $this->set($property, $arguments[0]);
        }

        if ('get' === $method) {
            return $this->get($property);
        }

        if ('add' === $method) {
            if (!\array_key_exists(0, $arguments)) {
                throw new \LogicException(sprintf('Please provide a value to add to %s', $property));
            }

            $this->add($property, $arguments[0]);

            return $this;
        }

        if (0 === strpos($name, 'is')) {
            if ($this->has($name)) {
                return $this->get($name);
            }

            return $this->get(lcfirst(substr($name, 2)));
        }

        throw new \BadMethodCallException(sprintf('The method "%s:%s" does not exists ', static::class, $name));
    }

    /**
     * @return $this
     */
    public function set(string $property, $value)
    {
        if (!\array_key_exists($property, $this->properties)) {
            throw new \LogicException(sprintf('Property %s is not present in instance of "%s".', $property, static::class));
        }

        $this->properties[$property] = $value;

        return $this;
    }

    public function get(string $property)
    {
        if (!\array_key_exists($property, $this->properties)) {
            throw new \LogicException(sprintf('Property %s is not present in instance of "%s".', $property, static::class));
        }

        return $this->properties[$property];
    }

    public function has(string $property): bool
    {
        return \array_key_exists($property, $this->properties);
    }

    public function add(string $property, $value): void
    {
        if (!\array_key_exists($property, $this->properties)) {
            throw new \LogicException(sprintf('Property "%s" is not present in instance of "%s".', $property, static::class));
        }

        $this->properties[$property][] = $value;
    }

    public function offsetExists(mixed $index): bool
    {
        return \array_key_exists($index, $this->properties);
    }

    public function offsetGet(mixed $index): mixed
    {
        return $this->get($index);
    }

    public function offsetSet(mixed $index, mixed $value): void
    {
        $this->set($index, $value);
    }

    public function offsetUnset(mixed $index): void
    {
        throw new \BadMethodCallException('Not available.');
    }

    public function getAlternateUrl()
    {
        return $this->alternateUrl;
    }

    public function getSelfUrl()
    {
        return $this->selfUrl;
    }

    public function __serialize(): array
    {
        return [$this->selfUrl, $this->alternateUrl, $this->properties];
    }

    public function __unserialize(array $data): void
    {
        [$this->selfUrl, $this->alternateUrl, $this->properties] = $data;
        $this->forms = [];
    }

    abstract protected function configure();
}
