<?php

namespace Rdlv\WordPress\Sywo\DependencyInjection\Compiler;

use Rdlv\WordPress\Sywo\Twig\Environment;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DecorateTwigEnvironment implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('twig')) {
            $definition = $container->getDefinition('twig');
            $definition->setClass(Environment::class);
            $definition->addMethodCall('setHooks', [new Reference('hooks')]);
        }
    }
}