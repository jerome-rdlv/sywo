<?php

namespace Rdlv\WordPress\Sywo\DependencyInjection\Compiler;

use Rdlv\WordPress\Sywo\RoutedPostRouter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RoutedPostRouterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $router = $container->getDefinition('router.default');
        $router->setClass(RoutedPostRouter::class);
    }
}