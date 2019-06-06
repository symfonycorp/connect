<?php

namespace SensioLabs\Connect\Tests\Api\Parser;

use PHPUnit\Framework\TestCase;
use SensioLabs\Connect\Api\Parser\VndComSensiolabsConnectXmlParser;
use SensioLabs\Connect\Api\Entity\Root;
use SensioLabs\Connect\Api\Entity\Index;
use SensioLabs\Connect\Api\Entity\User;
use SensioLabs\Connect\Api\Entity\Badge;

/**
 * @author Julien Galenski <julien.galenski@sensio.com>
 */
class VndComSensiolabsConnectXmlParserTest extends TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new VndComSensiolabsConnectXmlParser();
    }

    public function testParseRootAnonymous()
    {
        $rootXml = file_get_contents(__DIR__.'/../../../../../fixtures/root.xml');
        $root = $this->parser->parse($rootXml);

        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Root', $root);
        $this->assertInstanceOf('SensioLabs\Connect\Api\Model\Form', $root->getForm('search_users'));
    }

    public function getParseIndexTests()
    {
        return array(
            array('/../../../../../fixtures/users.xml', 'create_user', 'POST', 'https://connect.sensiolabs.com/api/users', 'SensioLabs\Connect\Api\Entity\User'),
        );
    }

    /**
     * @dataProvider getParseIndexTests
     */
    public function testParseIndex($xml, $formId, $method, $action, $class)
    {
        $indexXml = file_get_contents(__DIR__.$xml);
        $index = $this->parser->parse($indexXml);

        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $index);
        $this->assertSame($method, $index->getForm($formId)->getMethod());
        $this->assertSame($action, $index->getForm($formId)->getAction());
        $this->assertInstanceOf($class, $index[0]);
    }

    public function testParseBadges()
    {
        $indexXml = file_get_contents(__DIR__.'/../../../../../fixtures/badges.xml');
        $index = $this->parser->parse($indexXml);

        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $index);
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Badge', $index[0]);
    }

    public function testParseFoafPerson()
    {
        $rootXml = file_get_contents(__DIR__.'/../../../../../fixtures/user.xml');
        $user = $this->parser->parse($rootXml);

        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\User', $user);

        $badges = $user->getBadges();
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $badges);
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

        $this->assertInstanceOf('SensioLabs\Connect\Api\Model\Error', $error);
        $this->assertSame($expectedFields, $error->getEntityBodyParameters());
    }
}
