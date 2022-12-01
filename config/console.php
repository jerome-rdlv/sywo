<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    // @formatter:off
    $container->services()
        ->load('Rdlv\\WordPress\\Sywo\\Command\\', '../src/Command/*')
            ->tag('console.command')
    ;
};