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
use Symfony\Component\Form\Util\PropertyPath;

/**
 * AbstractEntity.
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
abstract class AbstractEntity implements \ArrayAccess
{
    private $api;
    private $selfUrl;
    private $alternateUrl;
    private $properties = array();
    private $image;
    private $formMethod;
    private $formAction;
    private $formFields = array();

    public function __construct($selfUrl = null, $alternateUrl = null)
    {
        $this->selfUrl = $selfUrl;
        $this->alternateUrl = $alternateUrl;
        $this->configure();
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

    public function setFormMethod($formMethod)
    {
        $this->formMethod = $formMethod;
    }

    public function getFormMethod()
    {
        return $this->formMethod;
    }

    public function setFormAction($formAction)
    {
        $this->formAction = $formAction;
    }

    public function getFormAction()
    {
        return $this->formAction;
    }

    public function addFormField($field, $value = null)
    {
        $this->formFields[$field] = $value;
    }

    public function getFormFields()
    {
        return $this->formFields;
    }

    public function setFormField($field, $value)
    {
        $this->formFields[$field] = $value;
    }

    public function setFormFields(array $formFields)
    {
        $this->formFields = $formFields;
    }

    public function submitForm(AbstractEntity $entity = null)
    {
        if (null === $entity) {
            $entity = $this;
        }
        
        $fields = $this->getFormFields();
        foreach ($fields as $key => $value) {
            $fields[$key] = $entity->get($key);
        }

        $response = $this->getApi()->submit($this->getFormAction(), $this->getFormMethod(), $fields);

        return $response;
    }

    public function __call($name, $arguments)
    {
        $method = substr($name, 0, 3);
        $property = lcfirst(substr($name, 3));

        if ('set' === $method) {
            if (!array_key_exists(0, $arguments)) {
                throw new \LogicException(sprintf('Please provide a value to set %s with', $name));
            }
            $this->set($property, $arguments[0]);
        } elseif ('get' === $method) {
            return $this->get($property);
        } elseif ('add' === $method) {
            $this->add($property, $arguments[0]);
        }
    }

    public function set($property, $value)
    {
        if (!array_key_exists($property, $this->properties)) {
            throw new \LogicException(sprintf('Property %s is not present in instance of "%s".', $property, get_class($this)));
        }

        $this->properties[$property] = $value;
    }

    public function get($property)
    {
        if (!array_key_exists($property, $this->properties)) {
            throw new \LogicException(sprintf('Property %s is not present in instance of "%s".', $property, get_class($this)));
        }

        return $this->properties[$property];
    }

    public function add($property, $value)
    {
        if (!array_key_exists($property, $this->properties)) {
            throw new \LogicException(sprintf('Property "%s" is not present in instance of "%s".', $property, get_class($this)));
        }

        $this->properties[$property][] = $value;
    }

    public function setApi(Api $api)
    {
        $this->api = $api;
    }

    public function getApi()
    {
        return $this->api;
    }

    public function refresh()
    {
        $response = $this->getApi()->get($this->selfUrl);
        $fresh = $response['entity'];
        foreach ($this->properties as $key => $property) {
            $this->set($key, $fresh->get($key));
        }

        $this->setFormAction($fresh->getFormAction());
        $this->setFormMethod($fresh->getFormMethod());
        $this->setFormFields($fresh->getFormFields());

        return $this;
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
        return $this->set($value);
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

    abstract protected function configure();
}

