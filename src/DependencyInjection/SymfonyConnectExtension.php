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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class SymfonyConnectExtension extends Extension
{
    private $securityEnabled = false;

    public function enableSecurity()
    {
        $this->securityEnabled = true;
    }

    public function getAlias()
    {
        return 'symfony_connect';
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('connect.xml');
        if ($this->securityEnabled) {
            $loader->load('security.xml');
        }

        $container->getDefinition('symfony_connect.oauth_consumer')
            ->replaceArgument(0, $config['app_id'])
            ->replaceArgument(1, $config['app_secret'])
            ->replaceArgument(2, $config['scope'])
            ->replaceArgument(3, $config['oauth_endpoint'])
        ;

        $container->getDefinition('symfony_connect.oauth_consumer')
            ->addMethodCall('setStrictChecks', [$config['strict_checks']])
        ;

        $container->getDefinition('symfony_connect.api')
            ->replaceArgument(0, $config['api_endpoint'])
        ;

        $container->getDefinition('symfony_connect.authenticator')
            ->replaceArgument(6, $config['start_template'])
            ->replaceArgument(7, $config['failure_template'])
        ;

        $container->getDefinition('symfony_connect.oauth_controller')
            ->replaceArgument(1, $config['start_template'])
            ->replaceArgument(2, $config['failure_template'])
        ;

        $container->setParameter('symfony_connect.api.app_id', $config['app_id']);
        $container->setParameter('symfony_connect.api.app_secret', $config['app_secret']);
    }
}
