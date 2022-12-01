<?php

namespace Rdlv\WordPress\Sywo\DependencyInjection\Compiler;

use Rdlv\WordPress\Sywo\Command\TranslationUpdateCommand;
use Symfony\Component\Config\Loader\ParamConfigurator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

class TranslationUpdateCommandPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $translationUpdate = $container->getDefinition(TranslationUpdateCommand::class);

        if ($container->hasDefinition('console.command.translation_extract')) {
            // configure translation update command
            $translationExtract = $container->getDefinition('console.command.translation_extract');
            $translationUpdate->setArguments(
                [
                    new Reference('console.command.translation_extract'),
                    new Parameter('translator.default_path'),
                    $translationExtract->getArgument(6),
                ]
            );
        } else {
            // remove translation update command
            $container->removeDefinition(TranslationUpdateCommand::class);
        }
    }
}