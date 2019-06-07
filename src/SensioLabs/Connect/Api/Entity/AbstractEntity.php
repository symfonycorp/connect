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

use SensioLabs\Connect\Api\Api;
use SensioLabs\Connect\Api\Model\Form;

/**
 * AbstractEntity.
 *
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
        $this->properties = array();
        $this->forms = array();
        $this->configure();
    }

    public function setApi(Api $api)
    {
        $this->api = $api;
        foreach ($this->properties as $property) {
            if ($property instanceof AbstractEntity) {
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

    public function submit($formId, AbstractEntity $entity = null)
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
            if (!is_array($fields[$key])) {
                $fields[$key] = $entity->get($key);
            } else {
                // we have a collection of fields that should be repeated.
                $template = $fields[$key];
                $fields[$key] = array();
                foreach ($entity->get($key) as $subEntity) {
                    $subFields = array();
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

        if (in_array($property, ['club', 'clubsUrl', 'project', 'projectsUrl', 'memberships', 'members'])) {
            @trigger_error(sprintf('The method "%s()" is deprecated since Connect 4.3 and will be removed in 5.0.', $name), E_USER_DEPRECATED);
        }

        if ('set' === $method) {
            if (!array_key_exists(0, $arguments)) {
                throw new \LogicException(sprintf('Please provide a value to set %s with', $name));
            }

            return $this->set($property, $arguments[0]);
        } elseif ('get' === $method) {
            return $this->get($property);
        } elseif ('add' === $method) {
            $this->add($property, $arguments[0]);

            return $this;
        } elseif ('is' === substr($name, 0, 2)) {
            return $this->get($name);
        }

        throw new \BadMethodCallException(sprintf('The method "%s:%s" does not exists ', get_class($this), $name));
    }

    public function set($property, $value)
    {
        if (!array_key_exists($property, $this->properties)) {
            throw new \LogicException(sprintf('Property %s is not present in instance of "%s".', $property, get_class($this)));
        }

        $this->properties[$property] = $value;

        return $this;
    }

    public function get($property)
    {
        if (!array_key_exists($property, $this->properties)) {
            throw new \LogicException(sprintf('Property %s is not present in instance of "%s".', $property, get_class($this)));
        }

        return $this->properties[$property];
    }

    public function has($property)
    {
        return array_key_exists($property, $this->properties);
    }

    public function add($property, $value)
    {
        if (!array_key_exists($property, $this->properties)) {
            throw new \LogicException(sprintf('Property "%s" is not present in instance of "%s".', $property, get_class($this)));
        }

        $this->properties[$property][] = $value;
    }

    public function offsetExists($index)
    {
        return array_key_exists($index, $this->properties);
    }

    public function offsetGet($index)
    {
        return $this->get($index);
    }

    public function offsetSet($index, $value)
    {
        $this->set($index, $value);
    }

    public function offsetUnset($index)
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

    public function serialize()
    {
        return serialize(array($this->selfUrl, $this->alternateUrl, $this->properties));
    }

    public function unserialize($str)
    {
        list($this->selfUrl, $this->alternateUrl, $this->properties) = unserialize($str);
        $this->forms = array();
    }

    abstract protected function configure();
}
