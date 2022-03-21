<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    // @formatter:off
    $container->services()
        ->load('Rdlv\\WordPress\\Sywo\\Command\\', '../../Command/*')
            ->args([
                service('console.command.translation_extract'),
                param('translator.default_path'),
                [], // Translator paths
            ])
            ->tag('console.command')
    ;
};