<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\Tests\Security\Authentication\Token;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;
use SymfonyCorp\Connect\Security\Authentication\Token\ConnectToken;

/**
 * @author Luc Vieillescazes <luc.vieillescazes@sensiolabs.com>
 */
class ConnectTokenTest extends TestCase
{
    public function testGetRolesWithUserInterfaceUser()
    {
        $user = new InMemoryUser('paul', 'xxxx', ['ROLE_SINGLE']);
        $token = new ConnectToken($user, 'xxxx', null, 'xxxx', null, ['ROLE_USER', 'ROLE_ADMIN']);
        $roles = $token->getRoleNames();

        $this->assertCount(1, $roles);
        $this->assertEquals('ROLE_SINGLE', \is_string($roles[0]) ? $roles[0] : $roles[0]->getRole());
    }

    public function testSerialization()
    {
        $user = new InMemoryUser('paul', 'xxxx', ['ROLE_SINGLE']);
        $token = new ConnectToken($user, 'xxxx', null, 'xxxx', null, ['ROLE_USER', 'ROLE_ADMIN']);
        $unserialized = unserialize(serialize($token));
        $this->assertSame($token->getScope(), $unserialized->getScope());
        $this->assertSame($token->getAccessToken(), $unserialized->getAccessToken());
        $this->assertSame($token->getFirewallName(), $unserialized->getFirewallName());
    }
}
