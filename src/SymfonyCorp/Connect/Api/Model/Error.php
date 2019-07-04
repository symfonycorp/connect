<?php

namespace SymfonyCorp\Connect\Api\Model;

/**
 * Error
 *
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class Error
{
    private $entityBodyParameters = array();

    public function getEntityBodyParameters()
    {
        return $this->entityBodyParameters;
    }

    public function hasEntityBodyParameter($name)
    {
        return array_key_exists($name, $this->entityBodyParameters);
    }

    public function addEntityBodyParameter($name)
    {
        if (!$this->hasEntityBodyParameter($name)) {
            $this->entityBodyParameters[$name] = array();
        }

        return $this;
    }

    public function addEntityBodyParameterError($name, $message)
    {
        $this->entityBodyParameters[$name][] = $message;

        return $this;
    }
}
