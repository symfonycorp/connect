<?php

namespace SensioLabs\Connect\Tests\Api\Parser;

use SensioLabs\Connect\Api\Parser\VndComSensiolabsConnectXmlParser;
use SensioLabs\Connect\Api\Entity\Root;
use SensioLabs\Connect\Api\Entity\Index;
use SensioLabs\Connect\Api\Entity\User;
use SensioLabs\Connect\Api\Entity\Club;
use SensioLabs\Connect\Api\Entity\Project;
use SensioLabs\Connect\Api\Entity\Badge;

/**
 * @author Julien Galenski <julien.galenski@sensio.com>
 */
class VndComSensiolabsConnectXmlParserTest extends \PHPUnit_Framework_TestCase
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
        $this->assertInstanceOf('SensioLabs\Connect\Api\Model\Form', $root->getForm('search_projects'));
        $this->assertInstanceOf('SensioLabs\Connect\Api\Model\Form', $root->getForm('search_users'));
        $this->assertInstanceOf('SensioLabs\Connect\Api\Model\Form', $root->getForm('search_clubs'));
    }

    public function getParseIndexTests()
    {
        return array(
            array('/../../../../../fixtures/users.xml', 'create_user', 'POST', 'https://connect.sensiolabs.com/api/users', 'SensioLabs\Connect\Api\Entity\User'),
            array('/../../../../../fixtures/clubs.xml', 'create_club', 'POST', 'https://connect.sensiolabs.com/api/clubs', 'SensioLabs\Connect\Api\Entity\Club'),
            array('/../../../../../fixtures/projects.xml', 'create_project', 'POST', 'https://connect.sensiolabs.com/api/projects', 'SensioLabs\Connect\Api\Entity\Project'),
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

        $memberships = $user->getMemberships();
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $memberships);
        $this->assertEquals(3, count($memberships));

        $projects = $user->getProjects();
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $projects);
        $this->assertEquals(13, count($projects));
    }

    public function testParseGroup()
    {
        $rootXml = file_get_contents(__DIR__.'/../../../../../fixtures/club.xml');
        $club = $this->parser->parse($rootXml);

        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Club', $club);
        $this->assertEquals(278, $club->getCumulatedBadges());
        $badges = $club->getBadges();
        $this->assertEquals(39, count($badges));
        $this->assertEquals('Personality of the year 2011', $badges[0]->getName());
        $this->assertEquals(1, $badges[0]->getCount());
    }

    public function testParseProject()
    {
        $rootXml = file_get_contents(__DIR__.'/../../../../../fixtures/project.xml');
        $project = $this->parser->parse($rootXml);

        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Project', $project);
        $this->assertSame(2, $project->getType());
        $this->assertSame("Symfony2 Bundle", $project->getTextualType());
        $this->assertTrue($project->getIsInternalGitRepositoryCreated());
    }

    public function testParseFormSelect()
    {
        $rootXml = file_get_contents(__DIR__.'/../../../../../fixtures/projects.xml');
        $project = $this->parser->parse($rootXml);

        $form = $project->getForm('create_project');
        $this->assertInstanceOf('SensioLabs\Connect\Api\Model\Form', $form);
        $options = array(
            'type' => array(
                 10 => 'Symfony2 Web Project',
                 11 => 'symfony1 Web Project',
                 9 => 'Silex Web Project',
                 8 => 'Laravel Web Project',
                 2 => 'Symfony2 Bundle',
                 4 => 'symfony1 Plugin',
                 7 => 'Drupal Module',
                 0 => 'PHP Web Project',
                 1 => 'PHP Library',
                 6 => 'Other',
            ),
            'isPrivate' => array(
                1 => 'Private',
                0 => 'Public',
            )
        );
        $this->assertSame($options, $form->getFieldsOptions());
        $this->assertSame($options['type'], $form->getFieldOptions('type'));
        $this->assertTrue($form->hasFieldOptions('type'));
        $this->assertFalse($form->hasFieldOptions('foobar'));
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
        $this->assertSame($expectedFields , $error->getEntityBodyParameters());
    }
}
