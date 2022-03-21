<?php

namespace Rdlv\WordPress\Sywo\DependencyInjection\Compiler;

use Rdlv\WordPress\Sywo\Command\TranslationsCommand;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExtractCommandTranslationPathsPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('console.command.translation_extract')) {
            $container->getDefinition(TranslationsCommand::class)->replaceArgument(
                2,
                $container->getDefinition('console.command.translation_extract')->getArgument(6)
            );
        }
    }
}