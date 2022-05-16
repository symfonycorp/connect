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

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class ConnectFactory extends AbstractFactory
{
    /**
     * Subclasses must return the id of a service which implements the
     * AuthenticationProviderInterface.
     *
     * @param string $id             The unique id of the firewall
     * @param array  $config         The options array for this listener
     * @param string $userProviderId The id of the user provider
     *
     * @return string never null, the id of the authentication provider
     */
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $provider = 'security.authentication.provider.symfony_connect.'.$id;
        $container
            ->setDefinition($provider, $this->createChildDefinition('security.authentication.provider.symfony_connect'))
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(1, $id)
        ;

        return $provider;
    }

    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string
    {
        $authenticatorId = 'symfony_connect.authenticator.'.$firewallName;
        $container
            ->setDefinition($authenticatorId, new ChildDefinition('symfony_connect.authenticator'))
            ->replaceArgument(2, new Reference($userProviderId));

        return $authenticatorId;
    }

    /**
     * Subclasses must return the id of the abstract listener template.
     *
     * Listener definitions should inherit from the AbstractAuthenticationListener
     * like this:
     *
     *    <service id="my.listener.id"
     *             class="My\Concrete\Classname"
     *             parent="security.authentication.listener.abstract"
     *             abstract="true" />
     *
     * In the above case, this method would return "my.listener.id".
     *
     * @return string
     */
    protected function getListenerId()
    {
        return 'security.authentication.listener.symfony_connect';
    }

    public function getPosition(): string
    {
        return 'form';
    }

    public function getKey(): string
    {
        return 'symfony_connect';
    }

    protected function createEntryPoint($container, $id, $config, $defaultEntryPointId): ?string
    {
        $entryPoint = 'security.authentication.entry_point.symfony_connect.'.$id;
        $container
            ->setDefinition($entryPoint, $this->createChildDefinition('security.authentication.entry_point.symfony_connect'))
        ;

        return $entryPoint;
    }

    private function createChildDefinition($id)
    {
        if (class_exists('Symfony\Component\DependencyInjection\ChildDefinition')) {
            return new ChildDefinition($id);
        }

        return new DefinitionDecorator($id);
    }

    public function getPriority(): int
    {
        return 0;
    }
}
