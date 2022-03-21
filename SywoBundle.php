<?php

namespace Rdlv\WordPress\Sywo;

use Rdlv\WordPress\Sywo\DependencyInjection\Compiler\DecorateTwigEnvironment;
use Rdlv\WordPress\Sywo\DependencyInjection\Compiler\ExtractCommandTranslationPathsPass;
use Rdlv\WordPress\Sywo\DependencyInjection\Compiler\GlobalTwigVarsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SywoBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new GlobalTwigVarsPass());
        $container->addCompilerPass(new ExtractCommandTranslationPathsPass());
        $container->addCompilerPass(new DecorateTwigEnvironment());
    }
}