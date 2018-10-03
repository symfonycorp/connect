<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\Api\Model;

/**
 * Form.
 *
 * @author Julien Galenski <julien.galenski@sensio.com>
 */
class Form
{
    private $action;
    private $method;
    private $fields;
    private $fieldsOptions;

    public function __construct($action, $method, $fields = array(), $fieldsOptions = array())
    {
        $this->action = $action;
        $this->method = $method;
        $this->fields = $fields;
        $this->fieldsOptions = $fieldsOptions;
    }

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    public function addField($key, $value)
    {
        $this->fields[$key] = $value;
    }

    public function getField($key)
    {
        return $this->fields[$key];
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getFieldsOptions()
    {
        return $this->fieldsOptions;
    }

    public function hasFieldOptions($field)
    {
        return array_key_exists($field, $this->fieldsOptions);
    }

    public function getFieldOptions($field)
    {
        if (!array_key_exists($field, $this->fieldsOptions)) {
            throw new \InvalidArgumentException(sprintf('The field "%s" does not exist. Existing fields: "%s"', $field, implode('", "', array_keys($this->fieldsOptions))));
        }

        return $this->fieldsOptions[$field];
    }

    public function setFieldOptions($field, $options)
    {
        $this->fieldsOptions[$field] = $options;
    }
}
