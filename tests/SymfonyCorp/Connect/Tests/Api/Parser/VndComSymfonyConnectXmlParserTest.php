<?php

namespace SymfonyCorp\Connect\Tests\Api\Parser;

use SymfonyCorp\Connect\Api\Parser\VndComSymfonyConnectXmlParser;
use SymfonyCorp\Connect\Api\Entity\Root;
use SymfonyCorp\Connect\Api\Entity\Index;
use SymfonyCorp\Connect\Api\Entity\User;
use SymfonyCorp\Connect\Api\Entity\Club;
use SymfonyCorp\Connect\Api\Entity\Badge;
use PHPUnit\Framework\TestCase;

/**
 * @author Julien Galenski <julien.galenski@sensio.com>
 */
class VndComSymfonyConnectXmlParserTest extends TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new VndComSymfonyConnectXmlParser();
    }

    public function testParseRootAnonymous()
    {
        $rootXml = file_get_contents(__DIR__.'/../../../../../fixtures/root.xml');
        $root = $this->parser->parse($rootXml);

        $this->assertInstanceOf('SymfonyCorp\Connect\Api\Entity\Root', $root);
        $this->assertInstanceOf('SymfonyCorp\Connect\Api\Model\Form', $root->getForm('search_users'));
    }

    public function getParseIndexTests()
    {
        return array(
            array('/../../../../../fixtures/users.xml', 'create_user', 'POST', 'https://connect.symfony.com/api/users', 'SymfonyCorp\Connect\Api\Entity\User'),
        );
    }

    /**
     * @dataProvider getParseIndexTests
     */
    public function testParseIndex($xml, $formId, $method, $action, $class)
    {
        $indexXml = file_get_contents(__DIR__.$xml);
        $index = $this->parser->parse($indexXml);

        $this->assertInstanceOf('SymfonyCorp\Connect\Api\Entity\Index', $index);
        $this->assertSame($method, $index->getForm($formId)->getMethod());
        $this->assertSame($action, $index->getForm($formId)->getAction());
        $this->assertInstanceOf($class, $index[0]);
    }

    public function testParseBadges()
    {
        $indexXml = file_get_contents(__DIR__.'/../../../../../fixtures/badges.xml');
        $index = $this->parser->parse($indexXml);

        $this->assertInstanceOf('SymfonyCorp\Connect\Api\Entity\Index', $index);
        $this->assertInstanceOf('SymfonyCorp\Connect\Api\Entity\Badge', $index[0]);
    }

    public function testParseFoafPerson()
    {
        $rootXml = file_get_contents(__DIR__.'/../../../../../fixtures/user.xml');
        $user = $this->parser->parse($rootXml);

        $this->assertInstanceOf('SymfonyCorp\Connect\Api\Entity\User', $user);

        $badges = $user->getBadges();
        $this->assertInstanceOf('SymfonyCorp\Connect\Api\Entity\Index', $badges);
        $this->assertEquals(20, count($badges));
    }

    public function testParseErrors()
    {
        $xml = file_get_contents(__DIR__.'/../../../../../fixtures/error.xml');
        $error = $this->parser->parse($xml);

        $expectedFields = array(
            'foo' => array(
                0 => 'This value should not be null.',
                1 => 'This value should not be blank.',
            ),
            'bar' => array(
                0 => 'This value should be equals to 6.',
            ),
        );

        $this->assertInstanceOf('SymfonyCorp\Connect\Api\Model\Error', $error);
        $this->assertSame($expectedFields, $error->getEntityBodyParameters());
    }
}
