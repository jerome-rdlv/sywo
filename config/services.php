<?php

use Rdlv\WordPress\Sywo\CsrfTokenManager;
use Rdlv\WordPress\Sywo\CsrfTokenManagerFactory;
use Rdlv\WordPress\Sywo\EventListener\RenderEventSubscriber;
use Rdlv\WordPress\Sywo\EventListener\WebDebugToolbarListener;
use Rdlv\WordPress\Sywo\FormHooks;
use Rdlv\WordPress\Sywo\Hooks;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    // @formatter:off
    $container->services()
        ->set('request_stack', RequestStack::class)
            ->public()
        ->set('event_dispatcher', EventDispatcher::class)
            ->public()
        ->set('sywo.hooks', Hooks::class)
            ->args([
                param('sywo.namespace'),
                service('twig')->nullOnInvalid(),
                service('twig.loader.native_filesystem')->nullOnInvalid()
            ])
            ->public()
            ->alias(Hooks::class, 'sywo.hooks')
        ->set('sywo.twig.filter', RenderEventSubscriber::class)
            ->args([service('sywo.hooks')])
            ->tag('kernel.event_subscriber')
        ->set('sywo.form_hooks', FormHooks::class)
            ->args([service('sywo.hooks')])
            ->tag('form.type_extension')
            ->alias(FormHooks::class, 'sywo.form_hooks')
        ->set('security.csrf.token_manager.wp', CsrfTokenManager::class)
            ->alias(CsrfTokenManager::class, 'security.csrf.token_manager.wp')
        ->set('security.csrf.token_manager.factory', CsrfTokenManagerFactory::class)
            ->tag('container.service_subscriber')
        ->set('sywo.wdt', WebDebugToolbarListener::class)
            ->call('setWdt', [service('web_profiler.debug_toolbar')->nullOnInvalid()])
            ->tag('container.service_subscriber')
            ->tag('kernel.event_subscriber')
            ->autowire()
    ;
};
