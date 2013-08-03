<?php

namespace SensioLabs\Connect\Bridge\Symfony\Form;

use SensioLabs\Connect\Exception\ApiClientException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

/**
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class ErrorTranslator
{
    /**
     * Bind all errors from ApiClientException $e to $form.
     *
     * You can override mapping between parameters in ApiClientException::getError
     * and parameters in FormInterface by giving a $map.
     * If string association is not enough, you can use a callable to
     * return a sub-form:
     *
     *   ->translate($form, $e, array(
     *       'password' => function($form) { return $form->get('rawPassword')->get('first'); },
     *       'foo'      => 'bar',
     *   ))
     *
     * @param FormInterface       $form The form
     * @param ApiClientException  $e    The exception
     * @param string[]|callable[] $map  The mapping between api parameters and form parameters
     *
     * @return FormInterface The form
     */
    public function translate(FormInterface $form, ApiClientException $e, array $map = array())
    {
        if (!$e->getError()) {
            $form->addError(new FormError($e->getMessage()));

            return $form;
        }

        foreach ($e->getError()->getEntityBodyParameters() as $parameterName => $messages) {
            $widget = $form;
            if (array_key_exists($parameterName, $map)) {
                if (is_callable($map[$parameterName])) {
                    $widget = $map[$parameterName]($form);
                    if (!$widget instanceof FormInterface) {
                        throw new \LogicException(sprintf('The callable ("$map[%s]") should return a FormInterface', $parameterName));
                    }
                } else {
                    $widget = $form->get($map[$parameterName]);
                }
            } elseif ($form->has($parameterName)) {
                $widget = $form->get($parameterName);
            }

            foreach ($messages as $message) {
                if (null === $widget->getParent()) {
                    $widget->addError(new FormError(sprintf('%s: %s', $parameterName, $message)));
                } else {
                    $widget->addError(new FormError($message));
                }
            }
        }

        return $form;
    }
}
