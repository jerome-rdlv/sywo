<?php

namespace Rdlv\WordPress\Sywo\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class GlobalTwigVarsPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('twig')) {
            $definition = $container->getDefinition('twig');
            $definition->addMethodCall('addGlobal', [
                'sywo_namespace',
                '%sywo.namespace%',
            ]);
        }
    }
}