<?php

namespace Rdlv\WordPress\Sywo;

use Exception;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\HttpKernel\KernelEvents;

abstract class Kernel extends BaseKernel implements EventSubscriberInterface
{
    private string $namespace;

    public function __construct(string $namespace = 'sywo')
    {
        $this->namespace = $namespace;

        parent::__construct(
            defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'production',
            defined('WP_DEBUG') && WP_DEBUG
        );
    }

    public function init(KernelEvent $event): void
    {
        if ($event->isMainRequest()) {
            /** @var Hooks $hooks */
            $hooks = $this->container->get('sywo.hooks');
            $hooks->do('init', $hooks);
        }
    }

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->setParameter('kernel.root_dir', $this->getProjectDir());
        $container->setParameter('sywo.namespace', $this->namespace);
    }

    /**
     * @throws Exception
     */
    public function getBuildDir(): string
    {
        if (isset($_SERVER['APP_BUILD_DIR'])) {
            return $_SERVER['APP_BUILD_DIR'].'/'.$this->environment;
        }

        return parent::getCacheDir();
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['init'],
        ];
    }
}