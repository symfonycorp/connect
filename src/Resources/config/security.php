<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use SymfonyCorp\Connect\Controller\OAuthController;
use SymfonyCorp\Connect\Security\Authentication\Provider\ConnectAuthenticationProvider;
use SymfonyCorp\Connect\Security\ConnectAuthenticator;
use SymfonyCorp\Connect\Security\EntryPoint\ConnectEntryPoint;
use SymfonyCorp\Connect\Security\EventListener\LoginSuccessEventListener;
use SymfonyCorp\Connect\Security\Firewall\ConnectAuthenticationListener;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('security.authentication.listener.symfony_connect')
        ->class(ConnectAuthenticationListener::class)
        ->parent('security.authentication.listener.abstract')
        ->abstract()
        ->call('setOAuthConsumer', [service('symfony_connect.oauth_consumer')])
        ->call('setOAuthCallback', ['symfony_connect_callback'])
        ->call('setApi', [service('symfony_connect.api')])
        ->call('setRethrowException', [param('kernel.debug')]);

    $services->set('security.authentication.provider.symfony_connect')
        ->class(ConnectAuthenticationProvider::class)
        ->abstract()
        ->arg(0, '')
        ->arg(1, '');

    $services->set('security.authentication.entry_point.symfony_connect')
        ->class(ConnectEntryPoint::class)
        ->arg(0, service('symfony_connect.oauth_consumer'))
        ->arg(1, service('security.http_utils'))
        ->arg(2, 'symfony_connect_callback');

    $services->set('symfony_connect.oauth_controller')
        ->class(OAuthController::class)
        ->arg(0, service('security.authentication.entry_point.symfony_connect'))
        ->arg(1, '')
        ->arg(2, '')
        ->tag('controller.service_arguments');

    $services->alias(OAuthController::class, 'symfony_connect.oauth_controller')
        ->public();

    $services->set('symfony_connect.authenticator')
        ->class(ConnectAuthenticator::class)
        ->arg(0, service('symfony_connect.oauth_consumer'))
        ->arg(1, service('symfony_connect.api'))
        ->arg(2, '')
        ->arg(3, service('security.http_utils'))
        ->arg(4, service('logger'))
        ->arg(5, '')
        ->arg(6, '')
        ->call('setRethrowException', [param('kernel.debug')]);

    $services->set('symfony_connect.login_success_listener')
        ->class(LoginSuccessEventListener::class)
        ->arg(0, service('Doctrine\ORM\EntityManagerInterface')->nullOnInvalid())
        ->tag('kernel.event_subscriber');
};
