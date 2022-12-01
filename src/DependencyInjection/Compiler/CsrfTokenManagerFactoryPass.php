<?php

namespace Rdlv\WordPress\Sywo\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Csrf\CsrfTokenManager as CsrfTokenManagerNative;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * This is needed if a form is created when no session available (for example after headers are sent).
 */
class CsrfTokenManagerFactoryPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('security.csrf.token_manager')) {
            $service = $container->getDefinition('security.csrf.token_manager');
            $container->setDefinition('security.csrf.token_manager.native', $service);
            $definition = new Definition(CsrfTokenManagerInterface::class);
            $definition->setFactory(new Reference('security.csrf.token_manager.factory'));
            $container->setDefinition('security.csrf.token_manager', $definition);
            $container->setAlias(CsrfTokenManagerNative::class, 'security.csrf.token_manager.native');
        }
    }
}