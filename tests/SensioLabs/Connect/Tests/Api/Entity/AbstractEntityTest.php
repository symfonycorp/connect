<?php

namespace SensioLabs\Connect\Tests\Api\Entity;

use PHPUnit\Framework\TestCase;
use SensioLabs\Connect\Api\Entity\AbstractEntity;

/**
 * AbstractEntityTest.
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class AbstractEntityTest extends TestCase
{
    public function setUp()
    {
        $this->entity = new Entity('http://foo.bar', 'http://bar.foo');
        $this->clone = clone $this->entity;
        $this->entity->set('clone', $this->clone);
    }

    public function testGetSelfUrl()
    {
        $this->assertEquals('http://foo.bar', $this->entity->getSelfUrl());
    }

    public function testGetAlternateUrl()
    {
        $this->assertEquals('http://bar.foo', $this->entity->getAlternateUrl());
    }

    public function testSetApiIsPropagatedToEntityInstances()
    {
        $api = $this->getMockBuilder('SensioLabs\Connect\Api\Api')
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->entity->setApi($api);
        $this->assertSame($api, $this->entity->getApi());
        $this->assertSame($api, $this->entity->get('clone')->getApi());
    }

    public function testApiIsNotSerialized()
    {
        $api = $this->getMockBuilder('SensioLabs\Connect\Api\Api')
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->entity->setApi($api);

        $unserializedEntity = unserialize(serialize($this->entity));

        $this->assertInstanceOf(get_class($this->entity), $unserializedEntity);
        $this->assertNull($unserializedEntity->getApi());
    }

    public function testHas()
    {
        $this->assertFalse($this->entity->has('foobar'));
        $this->assertTrue($this->entity->has('clone'));
    }

    public function testIs()
    {
        $this->assertTrue($this->entity->isEnabled());
        $this->assertFalse($this->entity->isPublished());
    }

    /**
     * @expectedException LogicException
     */
    public function testSetThrowsLogicExceptionIfPropertyIsUndefined()
    {
        $this->entity->set('foobar', 'bla');
    }

    /**
     * @expectedException LogicException
     */
    public function testGetThrowsLogicExceptionIfPropertyIsUndefined()
    {
        $this->entity->get('foobar');
    }

    public function testCallRedirectUnknownMethodName()
    {
        $this->assertSame($this->clone, $this->entity->getClone());

        $this->entity->setClone(clone $this->clone);
        $this->assertFalse($this->clone === $this->entity->getClone());

        $this->entity->addItems('foobar');
        $items = $this->entity->getItems();
        $this->assertEquals('foobar', $items[0]);
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testArrayAccessForbidUnset()
    {
        unset($this->entity['clone']);
    }

    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage The method "SensioLabs\Connect\Tests\Api\Entity\Entity:FooBar" does not exists
     */
    public function testBadMethodCallException()
    {
        $this->entity->FooBar();
    }

    public function testArrayAccess()
    {
        $this->assertSame($this->clone, $this->entity['clone']);

        $this->entity['clone'] = clone $this->clone;
        $this->assertFalse($this->clone === $this->entity['clone']);

        $this->assertTrue(isset($this->entity['clone']));
    }
}

class Entity extends AbstractEntity
{
    protected function configure()
    {
        $this->addProperty('foo')
             ->addProperty('clone')
             ->addProperty('isEnabled', true)
             ->addProperty('isPublished', false)
             ->addProperty('items', array());
    }
}
