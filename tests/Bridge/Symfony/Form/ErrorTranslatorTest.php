<?php

namespace SymfonyCorp\Connect\Tests\Bridge\Symfony\Form;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Forms;
use SymfonyCorp\Connect\Api\Model\Error;
use SymfonyCorp\Connect\Bridge\Symfony\Form\ErrorTranslator;
use SymfonyCorp\Connect\Exception\ApiClientException;

class ErrorTranslatorTest extends TestCase
{
    private ErrorTranslator $errorTranslator;
    private $formBuilder;

    protected function setUp(): void
    {
        $this->errorTranslator = new ErrorTranslator();
        $this->formBuilder = Forms::createFormFactory()->createBuilder();
    }

    public function testTranslate()
    {
        $parameters = [
            'foo' => ['foo is required'],
            'bar' => ['bar is invalid'],
        ];

        $exception = $this->createException($parameters);

        $form = $this->formBuilder->add('foo')->add('bar')->add('baz')->getForm();

        $form = $this->errorTranslator->translate($form, $exception);

        $this->assertCount(0, $form->getErrors());
        $this->assertCount(1, $form->get('foo')->getErrors());
        $this->assertCount(1, $form->get('bar')->getErrors());
        $this->assertCount(0, $form->get('baz')->getErrors());
    }

    public function testTranslateBubble()
    {
        $parameters = [
            'bubble' => ['bubble is required'],
        ];

        $exception = $this->createException($parameters);

        $form = $this->formBuilder->add('foo')->getForm();

        $form = $this->errorTranslator->translate($form, $exception);

        $this->assertCount(1, $form->getErrors());
        $this->assertCount(0, $form->get('foo')->getErrors());
    }

    public function testTranslateStringMap()
    {
        $parameters = [
            'bar' => ['bar is required'],
        ];

        $exception = $this->createException($parameters);

        $form = $this->formBuilder->add('foo')->getForm();

        $form = $this->errorTranslator->translate($form, $exception, ['bar' => 'foo']);

        $this->assertCount(0, $form->getErrors());
        $this->assertCount(1, $form->get('foo')->getErrors());
    }

    public function testTranslateCallableMap()
    {
        $parameters = [
            'bar' => ['bar is required'],
        ];

        $exception = $this->createException($parameters);

        $form = $this->formBuilder->add('foo')->getForm();

        $form = $this->errorTranslator->translate($form, $exception, ['bar' => function ($form) {
            return $form->get('foo');
        }]);

        $this->assertCount(0, $form->getErrors());
        $this->assertCount(1, $form->get('foo')->getErrors());
    }

    public function testTranslateCallableMapThrowException()
    {
        $this->expectException(\LogicException::class);

        $parameters = [
            'bar' => ['bar is required'],
        ];

        $exception = $this->createException($parameters);

        $form = $this->formBuilder->add('foo')->getForm();

        $this->errorTranslator->translate($form, $exception, ['bar' => function ($form) {
            return;
        }]);
    }

    public function testTranslateWithEmptyError()
    {
        $form = $this->formBuilder->add('foo')->getForm();

        $exception = new ApiClientException('403', '', 'Unauthorized', [], null);

        $form = $this->errorTranslator->translate($form, $exception);
        $this->assertInstanceOf('Symfony\Component\Form\Form', $form);
        $errors = $form->getErrors();
        // BC layer for symfony 2.5
        if ($errors instanceof \Traversable) {
            $errors = iterator_to_array($errors);
        }
        $this->assertCount(1, $errors);
        $error = reset($errors);
        $this->assertInstanceOf('Symfony\Component\Form\FormError', $error);
        $this->assertSame('Unauthorized', $error->getMessage());
        $this->assertCount(0, $form->get('foo')->getErrors());
    }

    private function createException(array $parameters = [])
    {
        $error = new Error();

        foreach ($parameters as $parameter => $messages) {
            $error->addEntityBodyParameter($parameter);
            foreach ($messages as $message) {
                $error->addEntityBodyParameterError($parameter, $message);
            }
        }

        return new ApiClientException(null, null, null, [], $error);
    }
}
