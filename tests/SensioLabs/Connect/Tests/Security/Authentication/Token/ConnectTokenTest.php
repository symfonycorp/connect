<?php

/*
 * This file is part of the SensioLabs Connect package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Connect\Tests\Security\Authentication\Token;

use SensioLabs\Connect\Security\Authentication\Token\ConnectToken;
use Symfony\Component\Security\Core\User\User;

/**
 * ConnectTokenTest
 *
 * @author Luc Vieillescazes <luc.vieillescazes@sensiolabs.com>
 */
class ConnectTokenTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRolesWithStringUser()
    {
        $token = new ConnectToken('paul', 'xxxx', null, 'xxxx', null, array('ROLE_USER', 'ROLE_ADMIN'));
        $roles = $token->getRoles();

        $this->assertCount(2, $roles);
        $this->assertEquals('ROLE_USER', $roles[0]->getRole());
        $this->assertEquals('ROLE_ADMIN', $roles[1]->getRole());
    }

    public function testGetRolesWithUserInterfaceUser()
    {
        $user = new User('paul', 'xxxx', array('ROLE_SINGLE'));
        $token = new ConnectToken($user, 'xxxx', null, 'xxxx', null, array('ROLE_USER', 'ROLE_ADMIN'));
        $roles = $token->getRoles();

        $this->assertCount(1, $roles);
        $this->assertEquals('ROLE_SINGLE', $roles[0]->getRole());
    }
}
