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
        $this->assertSame(0, $project->getTypeFromTextual('Website'));
        $this->assertSame(1, $project->getTypeFromTextual('Library'));
        $this->assertSame(2, $project->getTypeFromTextual('Symfony Bundle'));
        $this->assertSame(3, $project->getTypeFromTextual('CLI Application'));
        $this->assertSame(4, $project->getTypeFromTextual('symfony 1.x plugin'));
        $this->assertSame(5, $project->getTypeFromTextual('Symfony Distribution'));
        $this->assertSame(6, $project->getTypeFromTextual('Other'));
    }
}
