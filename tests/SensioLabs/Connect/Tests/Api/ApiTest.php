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
            '/users\/.+$/'         => 'user.xml',
            '/clubs$/'             => 'clubs.xml',
            '/clubs\/.+$/'         => 'club.xml',
            '/projects$/'          => 'projects.xml',
            '/projects\/.+$/'      => 'project.xml',
        );

        $response = $this->getMock('Buzz\Message\Response', array('setStatusCode'));
        $browser = $this->getMock('Buzz\Browser', array('get', 'submit'));

        $callback = function($url, $headers) use ($map) {
            $response = new Response();

            foreach ($map as $pattern => $file) {
                if (0 < preg_match($pattern, $url)) {
                    $response->setContent(file_get_contents(__DIR__.'/../../../../fixtures/'.$file));
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

    public function testAccessTokenAccessorsAndMutators()
    {
        $this->api->setAccessToken('foobar');
        $this->assertEquals('foobar', $this->api->getAccessToken());
        $this->api->resetAccessToken();
        $this->assertNull($this->api->getAccessToken());
    }

    public function testGetRootAsAnonymous()
    {
        $rootXml = file_get_contents(__DIR__.'/../../../../fixtures/root.xml');
        $root = $this->parser->parse($rootXml);
        $this->assertTrue($root instanceof Entity\Root);

        $root->setApi($this->api);

        // Test on existence of Forms
        $this->assertInstanceOf('SensioLabs\Connect\Api\Model\Form', $root->getForm('search_projects'));
        $this->assertInstanceOf('SensioLabs\Connect\Api\Model\Form', $root->getForm('search_users'));
        $this->assertInstanceOf('SensioLabs\Connect\Api\Model\Form', $root->getForm('search_clubs'));

        // Test Project methods
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $root->getLastProjects());
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $root->searchProjects('tagadajones'));
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Project', $root->getProject('1111-22222-3333'));

        // Test Clubs methods
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $root->getLastClubs());
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $root->searchClubs('tagadajones'));
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Club', $root->getClub('1111-22222-3333'));

        // Test Users methods
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $root->getLastUsers());
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\Index', $root->searchUsers('tagadajones'));
        $this->assertInstanceOf('SensioLabs\Connect\Api\Entity\User', $root->getUser('1111-22222-3333'));
    }
}

