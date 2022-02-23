<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('symfony_connect');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('app_id')->isRequired()->end()
                ->scalarNode('app_secret')->isRequired()->end()
                ->scalarNode('scope')->isRequired()->end()
                ->scalarNode('oauth_endpoint')->defaultValue('https://connect.symfony.com')->end()
                ->scalarNode('api_endpoint')->defaultValue('https://connect.symfony.com/api')->end()
                ->scalarNode('start_template')->defaultNull()->end()
                ->scalarNode('failure_template')->defaultNull()->end()
                ->booleanNode('strict_checks')->defaultValue(true)->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
