<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use SymfonyCorp\Connect\DependencyInjection\Security\Factory\AuthenticatorConnectFactory;
use SymfonyCorp\Connect\DependencyInjection\Security\Factory\ConnectFactory;
use SymfonyCorp\Connect\DependencyInjection\Security\UserProvider\ConnectInMemoryFactory;
use SymfonyCorp\Connect\DependencyInjection\SymfonyConnectExtension;

/**
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class SymfonyConnectBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new SymfonyConnectExtension();
        }

        return $this->extension;
    }

    public function build(ContainerBuilder $container)
    {
        if ($container->hasExtension('security')) {
            $container->getExtension('symfony_connect')->enableSecurity();
            $container->getExtension('security')->addUserProviderFactory(new ConnectInMemoryFactory());

            if (interface_exists(AuthenticatorFactoryInterface::class)) {
                $securityFactory = new AuthenticatorConnectFactory();
            } else {
                $securityFactory = new ConnectFactory();
            }

            if (method_exists($container->getExtension('security'), 'addAuthenticatorFactory')) {
                $container->getExtension('security')->addAuthenticatorFactory($securityFactory);
            } else {
                // deprecated since Symfony 5.4
                $container->getExtension('security')->addSecurityListenerFactory($securityFactory);
            }
        }
    }
}
