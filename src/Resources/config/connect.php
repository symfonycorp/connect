<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use SymfonyCorp\Connect\Api\Api;
use SymfonyCorp\Connect\Api\Parser\VndComSymfonyConnectXmlParser;
use SymfonyCorp\Connect\Bridge\Symfony\Form\ErrorTranslator;
use SymfonyCorp\Connect\OAuthConsumer;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('symfony_connect.oauth_consumer')
        ->class(OAuthConsumer::class)
        ->arg(0, '')
        ->arg(1, '')
        ->arg(2, '')
        ->arg(3, null)
        ->arg(4, service(HttpClientInterface::class))
        ->arg(5, service('logger')->nullOnInvalid())
        ->tag('monolog.logger', ['channel' => 'symfony_connect']);

    $services->set('symfony_connect.api.parser')
        ->class(VndComSymfonyConnectXmlParser::class);

    $services->set('symfony_connect.error_translator')
        ->class(ErrorTranslator::class);

    $services->set('symfony_connect.api')
        ->class(Api::class)
        ->arg(0, '')
        ->arg(1, service(HttpClientInterface::class))
        ->arg(2, service('symfony_connect.api.parser'))
        ->arg(3, service('logger'))
        ->tag('monolog.logger', ['channel' => 'symfony_connect']);

    $services->alias(Api::class, 'symfony_connect.api');
};
