<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use SymfonyCorp\Connect\Controller\OAuthController;

return static function (RoutingConfigurator $routes): void {
    $routes->add('symfony_connect_callback', '/callback');

    $routes->add('symfony_connect_login', '/login')
        ->methods(['GET'])
        ->controller([OAuthController::class, 'newSession']);

    $routes->add('symfony_connect_start', '/start')
        ->methods(['GET'])
        ->controller([OAuthController::class, 'start']);

    $routes->add('symfony_connect_failure', '/failure')
        ->methods(['GET'])
        ->controller([OAuthController::class, 'failure']);
};
