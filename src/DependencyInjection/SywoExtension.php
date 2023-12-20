<?php

namespace Rdlv\WordPress\Sywo\DependencyInjection;

use Symfony\Bundle\FrameworkBundle\Command\TranslationUpdateCommand;
use Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class SywoExtension extends Extension
{
    /**
     * @inheritDoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new PhpFileLoader($container, new FileLocator(dirname(__DIR__, 2) . '/config'));
        $loader->load('services.php');

        if ($this->hasConsole()) {
            $loader->load('console.php');

            if (!class_exists(TranslationUpdateCommand::class)) {
                $container->removeDefinition(TranslationUpdateCommand::class);
            }
        }

        if (!class_exists(WebDebugToolbarListener::class)) {
            $container->removeDefinition('sywo.wdt');
        }
    }

    protected function hasConsole(): bool
    {
        return class_exists(Application::class);
    }
}