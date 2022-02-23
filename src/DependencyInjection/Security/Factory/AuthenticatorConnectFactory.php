<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;

if (interface_exists(EntryPointFactoryInterface::class)) {
    class AuthenticatorConnectFactory extends ConnectFactory implements AuthenticatorFactoryInterface, EntryPointFactoryInterface
    {
        public function getPriority(): int
        {
            return 0;
        }
    }
} else {
    class AuthenticatorConnectFactory extends ConnectFactory implements AuthenticatorFactoryInterface
    {
        public function getPriority(): int
        {
            return 0;
        }
    }
}
