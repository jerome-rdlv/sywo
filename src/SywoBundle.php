<?php

namespace Rdlv\WordPress\Sywo;

use Rdlv\WordPress\Sywo\DependencyInjection\Compiler\DecorateTwigEnvironment;
use Rdlv\WordPress\Sywo\DependencyInjection\Compiler\TranslationUpdateCommandPass;
use Rdlv\WordPress\Sywo\DependencyInjection\Compiler\GlobalTwigVarsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SywoBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new GlobalTwigVarsPass());
        $container->addCompilerPass(new TranslationUpdateCommandPass());
        $container->addCompilerPass(new DecorateTwigEnvironment());
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}