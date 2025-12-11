<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use SymfonyCorp\Connect\Controller\OAuthController;
use SymfonyCorp\Connect\Security\Authentication\Provider\ConnectAuthenticationProvider;
use SymfonyCorp\Connect\Security\ConnectAuthenticator;
use SymfonyCorp\Connect\Security\EntryPoint\ConnectEntryPoint;
use SymfonyCorp\Connect\Security\EventListener\LoginSuccessEventListener;
use SymfonyCorp\Connect\Security\Firewall\ConnectAuthenticationListener;
use SymfonyCorp\Connect\Security\User\ConnectInMemoryUserProvider;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('security.authentication.listener.symfony_connect', ConnectAuthenticationListener::class)
        ->parent('security.authentication.listener.abstract')
        ->abstract()
        ->call('setOAuthConsumer', [service('symfony_connect.oauth_consumer')])
        ->call('setOAuthCallback', ['symfony_connect_callback'])
        ->call('setApi', [service('symfony_connect.api')])
        ->call('setRethrowException', [param('kernel.debug')]);

    $services->set('security.authentication.provider.symfony_connect', ConnectAuthenticationProvider::class)
        ->abstract()
        ->arg(0, abstract_arg('user provider'))
        ->arg(1, abstract_arg('firewallName / providerKey'));

    $services->set('security.authentication.entry_point.symfony_connect', ConnectEntryPoint::class)
        ->arg(0, service('symfony_connect.oauth_consumer'))
        ->arg(1, service('security.http_utils'))
        ->arg(2, 'symfony_connect_callback');

    $services->set('security.user.provider.symfony_connect_in_memory', ConnectInMemoryUserProvider::class)
        ->abstract();

    $services->set('symfony_connect.oauth_controller', OAuthController::class)
        ->arg(0, service('security.authentication.entry_point.symfony_connect'))
        ->arg(1, abstract_arg('start template'))
        ->arg(2, abstract_arg('failure template'))
        ->tag('controller.service_arguments');

    $services->alias(OAuthController::class, 'symfony_connect.oauth_controller')
        ->public();

    $services->set('symfony_connect.authenticator', ConnectAuthenticator::class)
        ->arg(0, service('symfony_connect.oauth_consumer'))
        ->arg(1, service('symfony_connect.api'))
        ->arg(2, abstract_arg('user provider'))
        ->arg(3, service('security.http_utils'))
        ->arg(4, service('logger'))
        ->arg(5, abstract_arg('start template'))
        ->arg(6, abstract_arg('failure template'))
        ->call('setRethrowException', [param('kernel.debug')]);

    $services->set('symfony_connect.login_success_listener', LoginSuccessEventListener::class)
        ->arg(0, service('Doctrine\ORM\EntityManagerInterface')->nullOnInvalid())
        ->tag('kernel.event_subscriber');
};
