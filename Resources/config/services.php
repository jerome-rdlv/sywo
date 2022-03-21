<?php

use Rdlv\WordPress\Sywo\CsrfTokenManager;
use Rdlv\WordPress\Sywo\CsrfTokenManagerFactory;
use Rdlv\WordPress\Sywo\FormHooks;
use Rdlv\WordPress\Sywo\Hooks;
use Rdlv\WordPress\Sywo\RequestFactory;
use Rdlv\WordPress\Sywo\Twig\SywoExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Request;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    // @formatter:off
    $container->services()
        ->set('hooks', Hooks::class)
            ->args([
                param('sywo.namespace'),
                service('twig')->nullOnInvalid(),
                service('twig.loader.native_filesystem')->nullOnInvalid()
            ])
            ->public()
            ->alias(Hooks::class, 'hooks')
        ->set('form_hooks', FormHooks::class)
            ->autowire()
            ->tag('form.type_extension')
            ->alias(FormHooks::class, 'form_hooks')
        ->set('request.factory', RequestFactory::class)
            ->tag('container.service_subscriber')
        ->set('request', Request::class)
            ->factory(service('request.factory'))
            ->autowire()
            ->public()
            ->alias(Request::class, 'request')
        ->set('security.csrf.token_manager.wp', CsrfTokenManager::class)
            ->alias(CsrfTokenManager::class, 'security.csrf.token_manager.wp')
        ->set('security.csrf.token_manager.factory', CsrfTokenManagerFactory::class)
            ->tag('container.service_subscriber')
        ->set(SywoExtension::class)
            ->args([
                service('translator')->nullOnInvalid(),
            ])
            ->autowire()
            ->tag('twig.extension')
//        ->load('Rdlv\\WordPress\\Sywo\\Twig\\', '../../Twig/*')
//            ->autowire()
//            ->tag('twig.extension')
    ;
};
