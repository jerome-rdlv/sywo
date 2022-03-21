<?php

namespace Rdlv\WordPress\Sywo\DependencyInjection;

use Rdlv\WordPress\Sywo\Command\TranslationsCommand;
use Symfony\Bundle\FrameworkBundle\Command\TranslationUpdateCommand;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class SywoExtension extends Extension
{
    /**
     * @inheritDoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new PhpFileLoader($container, new FileLocator(dirname(__DIR__) . '/Resources/config'));
        $loader->load('services.php');

        if ($this->hasConsole()) {
            $loader->load('console.php');

            if (!class_exists(TranslationUpdateCommand::class)) {
                $container->removeDefinition(TranslationsCommand::class);
            }
        }
    }

    protected function hasConsole(): bool
    {
        return class_exists(Application::class);
    }
}