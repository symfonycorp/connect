<?php

namespace SensioLabs\Connect\Tests\Api\Entity;

use SensioLabs\Connect\Api\Entity\Project;

/**
 * ProjectTest.
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class ProjectTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTypeFromTextual()
    {
        $project = new Project();
        $this->assertSame(10, $project->getTypeFromTextual('Symfony2 Web Project'));
        $this->assertSame(11, $project->getTypeFromTextual('symfony1 Web Project'));
        $this->assertSame(9, $project->getTypeFromTextual('Silex Web Project'));
        $this->assertSame(8, $project->getTypeFromTextual('Laravel Web Project'));
        $this->assertSame(2, $project->getTypeFromTextual('Symfony2 Bundle'));
        $this->assertSame(4, $project->getTypeFromTextual('symfony1 Plugin'));
    }
}
