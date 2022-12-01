<?php

namespace Rdlv\WordPress\Sywo;

use Exception;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

abstract class Kernel extends \Symfony\Component\HttpKernel\Kernel implements EventSubscriberInterface
{
    /** @var string */
    private $namespace;

    public function __construct(string $namespace = 'sywo')
    {
        $this->namespace = $namespace;

        parent::__construct(
            defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'production',
            defined('WP_DEBUG') ? WP_DEBUG : false
        );
    }

    public function init(KernelEvent $event)
    {
        if ($event->isMainRequest()) {
            /** @var Hooks $hooks */
            $hooks = $this->container->get('sywo.hooks');
            $hooks->do('init', $hooks);
        }
    }

    protected function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->setParameter('sywo.namespace', $this->namespace);
    }

    /**
     * @throws Exception
     * {@inheritdoc}
     */
    public function getBuildDir(): string
    {
        if (isset($_SERVER['APP_BUILD_DIR'])) {
            return $_SERVER['APP_BUILD_DIR'] . '/' . $this->environment;
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

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['init'],
        ];
    }
}