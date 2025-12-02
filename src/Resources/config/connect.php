<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use SymfonyCorp\Connect\Api\Api;
use SymfonyCorp\Connect\Api\Parser\VndComSymfonyConnectXmlParser;
use SymfonyCorp\Connect\Bridge\Symfony\Form\ErrorTranslator;
use SymfonyCorp\Connect\OAuthConsumer;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('symfony_connect.oauth_consumer', OAuthConsumer::class)
        ->arg(0, abstract_arg('app id'))
        ->arg(1, abstract_arg('app secret'))
        ->arg(2, abstract_arg('app scope'))
        ->arg(3, abstract_arg('oauth endpoint'))
        ->arg(4, service(HttpClientInterface::class))
        ->arg(5, service('logger')->nullOnInvalid())
        ->tag('monolog.logger', ['channel' => 'symfony_connect']);

    $services->set('symfony_connect.api.parser', VndComSymfonyConnectXmlParser::class);

    $services->set('symfony_connect.error_translator', ErrorTranslator::class);

    $services->set('symfony_connect.api', Api::class)
        ->arg(0, abstract_arg('API endpoint'))
        ->arg(1, service(HttpClientInterface::class))
        ->arg(2, service('symfony_connect.api.parser'))
        ->arg(3, service('logger'))
        ->tag('monolog.logger', ['channel' => 'symfony_connect']);

    $services->alias(Api::class, 'symfony_connect.api');
};
