<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\DependencyInjection\Security\UserProvider;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\UserProvider\UserProviderFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

/**
 * ConnectInMemoryFactory creates services for the memory provider.
 *
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class ConnectInMemoryFactory implements UserProviderFactoryInterface
{
    public function create(ContainerBuilder $container, $id, $config): void
    {
        $users = [];
        foreach ($config['users'] as $username => $roles) {
            $users[str_replace('_', '-', $username)] = $roles;
        }

        $definition = $container->setDefinition($id, $this->createChildDefinition('security.user.provider.symfony_connect_in_memory'));
        $definition->setArguments([$users]);
    }

    public function getKey(): string
    {
        return 'connect_memory';
    }

    public function addConfiguration(NodeDefinition $node): void
    {
        $node
            ->fixXmlConfig('user')
            ->children()
                ->arrayNode('users')
                    ->useAttributeAsKey('username')
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;
    }

    private function createChildDefinition($id)
    {
        if (class_exists(ChildDefinition::class)) {
            return new ChildDefinition($id);
        }

        return new DefinitionDecorator($id);
    }
}
