<?php

namespace SensioLabs\Connect\Tests\Api\Parser;

use SensioLabs\Connect\Api\Parser\VndComSensioLabsConnectXmlParser;
use SensioLabs\Connect\Api\Entity\Root;
use SensioLabs\Connect\Api\Entity\Index;
use SensioLabs\Connect\Api\Entity\User;
use SensioLabs\Connect\Api\Entity\Club;
use SensioLabs\Connect\Api\Entity\Project;
use SensioLabs\Connect\Api\Entity\Badge;

/**
* 
* @author Julien Galenski <julien.galenski@sensio.com>
*/
class VndComSensiolabsConnectXmlParserTest extends \PHPUnit_Framework_TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new VndComSensioLabsConnectXmlParser();
    }

    public function testParseRootAnonymous()
    {
        $rootXml = file_get_contents(__DIR__.'/../fixtures/root_anonymous.xml');
        $root = $this->parser->parse($rootXml);

        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Root', $root);
    }

    public function testParseRootConnected()
    {
        $rootXml = file_get_contents(__DIR__.'/../fixtures/root_authenticated.xml');
        $root = $this->parser->parse($rootXml);

        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Root', $root);
        $this->assertEquals('cnorris', $root->getCurrentUser()->getUsername());
    }

    /**
     * @dataProvider provider
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

    public function provider()
    {
        return array(
            array('/../fixtures/users.xml', 'create_user', 'POST', 'http://connect.sensiolabs.com/api/users', 'SensioLabs\Connect\Api\Entity\User'),
            array('/../fixtures/clubs.xml', 'create_club', 'POST', 'http://connect.sensiolabs.com/api/clubs', 'SensioLabs\Connect\Api\Entity\Club'),
            array('/../fixtures/projects.xml', 'create_project', 'POST', 'http://connect.sensiolabs.com/api/projects', 'SensioLabs\Connect\Api\Entity\Project'),
        );
    }

    public function testParseBadges()
    {
        $indexXml = file_get_contents(__DIR__.'/../fixtures/badges.xml');
        $index = $this->parser->parse($indexXml);

        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $index);
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Badge', $index[0]);
    }

    public function testParsePerson()
    {
        $rootXml = file_get_contents(__DIR__.'/../fixtures/user.xml');
        $user = $this->parser->parse($rootXml);

        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\User', $user);
    }

    public function testParseGroup()
    {
        $rootXml = file_get_contents(__DIR__.'/../fixtures/club.xml');
        $club = $this->parser->parse($rootXml);

        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Club', $club);
    }

    public function testParseProject()
    {
        $rootXml = file_get_contents(__DIR__.'/../fixtures/project.xml');
        $project = $this->parser->parse($rootXml);

        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Project', $project);
    }
}