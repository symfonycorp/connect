<?php

/*
 * This file is part of the SensioLabs Connect package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Connect\Test\Api;

use Buzz\Message\Response;
use SensioLabs\Connect\Api\Buzz\Browser;

use SensioLabs\Connect\Api\Api;
use SensioLabs\Connect\Api\Model\Form;
use SensioLabs\Connect\Api\Parser\VndComSensioLabsConnectXmlParser;
use SensioLabs\Connect\Api\Entity;

/**
 * ApitTest.
 *
 * @author Julien Galenski <julien.galenski@gmail.com>
 */
class ApiTest extends \PHPUnit_Framework_TestCase
{
    private $api;
    private $parser;

    public function setUp()
    {
        $map = array(
            '/users$/'             => 'users.xml',
            '/users?q=.+$/'        => 'users_authenticated.xml',
            '/users\/.+$/'         => 'user.xml',
            '/users\/.+?q=.+/'     => 'user_authenticated.xml',
            '/clubs$/'             => 'clubs.xml',
            '/clubs?q=.+$/'        => 'clubs_authenticated.xml',
            '/clubs\/.+$/'         => 'club.xml',
            '/clubs\/.+?q=.+$/'    => 'club_authenticated.xml',
            '/projects$/'          => 'projects.xml',
            '/projects?q=.+$/'     => 'projects_authenticated.xml',
            '/projects\/.+$/'      => 'project.xml',
            '/projects\/.+?q=.+$/' => 'project_authenticated.xml'
        );

        $response = $this->getMock('Buzz\Message\Response', array('setStatusCode'));
        
        $browser = $this->getMock('Buzz\Browser', array('get', 'submit'));

        $callback = function($url, $headers) use ($map) {
            $response = new Response();

            foreach ($map as $pattern => $file) {
                if (0 < preg_match($pattern, $url)) {
                    $response->setContent(file_get_contents(__DIR__.'/fixtures/'.$file));
                }
            }

            return $response;
        };

        $browser->expects($this->any())
                ->method('get')
                ->will($this->returnCallback($callback));
        $browser->expects($this->any())
                ->method('submit')
                ->will($this->returnCallback($callback));

        $this->api = new Api('http://connect.sensiolabs.com/api', $browser);
        $this->parser = new VndComSensioLabsConnectXmlParser();
    }

    public function testGetRootAsAnonymous()
    {
        $rootXml = file_get_contents(__DIR__.'/fixtures/root_anonymous.xml');
        $root = $this->parser->parse($rootXml);
        $this->assertTrue($root instanceof Entity\Root);

        $root->setApi($this->api);

        // Test on existence of Forms
        $this->assertInstanceOf('SensioLabs\Connect\Api\Model\Form', $root->getForm('search_projects'));
        $this->assertInstanceOf('SensioLabs\Connect\Api\Model\Form', $root->getForm('search_users'));
        $this->assertInstanceOf('SensioLabs\Connect\Api\Model\Form', $root->getForm('search_clubs'));

        // // Test Project methods
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $root->getLastProjects());
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $root->searchProjects('tagadajones'));
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Project', $root->getProject('1111-22222-3333'));

        // // Test Clubs methods
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $root->getLastClubs());
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $root->searchClubs('tagadajones'));
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Club', $root->getClub('1111-22222-3333'));

        // Test Users methods
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $root->getLastUsers());
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $root->searchUsers('tagadajones'));
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\User', $root->getUser('1111-22222-3333'));
    }

    public function testGetRootAsAuthenticatedUser()
    {
        $rootXml = file_get_contents(__DIR__.'/fixtures/root_authenticated.xml');
        $root = $this->parser->parse($rootXml);
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Root', $root);
        $this->assertEquals('cnorris', $root->getCurrentUser()->getUsername());
    }

    public function testCreateClubAsAuthenticatedUser()
    {
        $indexXml = file_get_contents(__DIR__.'/fixtures/clubs_authenticated.xml');
        $clubs = $this->parser->parse($indexXml);
        $clubs->setApi($this->api);

        $club = new Entity\Club();
        $club->setApi($this->api);
        $response = $clubs->submit('create_club', $club);
        $this->assertInstanceOf('Buzz\Message\Response', $response['response']);
    }

    public function testCreateProjectAsAuthenticatedUser()
    {
        $indexXml = file_get_contents(__DIR__.'/fixtures/projects_authenticated.xml');
        $projects = $this->parser->parse($indexXml);
        $projects->setApi($this->api);

        $project = new Entity\Project();
        $project->setApi($this->api);
        $response = $projects->submit('create_project', $project);
        $this->assertInstanceOf('Buzz\Message\Response', $response['response']);
    }

    public function testCreateUserAsAuthenticatedUser()
    {
        $indexXml = file_get_contents(__DIR__.'/fixtures/users_authenticated.xml');
        $users = $this->parser->parse($indexXml);
        $users->setApi($this->api);

        $user = new Entity\User();
        $user->setApi($this->api);
        $response = $users->submit('create_user', $user);
        $this->assertInstanceOf('Buzz\Message\Response', $response['response']);
    }
}
