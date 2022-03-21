<?php

namespace Rdlv\WordPress\Sywo;

use Exception;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class Kernel extends \Symfony\Component\HttpKernel\Kernel
{
    /** @var string */
    private $namespace;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;

        parent::__construct(
            defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'production',
            defined('WP_DEBUG') ? WP_DEBUG : false
        );
    }
    
    public function getService(string $serviceName)
    {
        $container = $this->getContainer();
        if (!$container->has($serviceName)) {
            throw new Exception(sprintf('Service %s not found.', $serviceName));
        }
        return $container->get($serviceName);
    }

    /**
     * @throws Exception
     */
    public function call(string $serviceName, string $method, array $arguments = [])
    {
        $this->getHooks()->do('call', $serviceName, $method, $arguments);
        $service = $this->getService($serviceName);
        if (!method_exists($service, $method)) {
            throw new Exception(sprintf('Method %s not found on service %s.', $method, $serviceName));
        }
        return call_user_func_array([$service, $method], $arguments);
    }

    private function getHooks()
    {
        return $this->getContainer()->get('hooks');
    }

    public function getContainer()
    {
        if (!$this->booted) {
            $this->boot();

            $container = parent::getContainer();

            $request = Request::createFromGlobals();

            /** @var Hooks $hooks */
            $hooks = $container->get('hooks');

            /** @var RequestStack $requestStack */
            $requestStack = $container->get('request_stack');

            /** @var EventDispatcherInterface $dispatcher */
            $dispatcher = $container->get('event_dispatcher');

            // init services
            $requestStack->push($request);
            $event = new RequestEvent($this, $request, HttpKernel::MAIN_REQUEST);
            $dispatcher->dispatch($event, KernelEvents::REQUEST);

            $hooks->do('init', $hooks, $container, $request);
        }

        return parent::getContainer();
    }

    protected function build(ContainerBuilder $container)
    {
        $container->setParameter('sywo.namespace', $this->namespace);
    }

    /**
     * @throws Exception
     * {@inheritdoc}
     */
    public function getCacheDir(): string
    {
        return $this->getDir(defined('CACHE_DIR') ? CACHE_DIR : $this->getContentDir() . '/cache', $this->namespace);
    }

    /**
     * @throws Exception
     * {@inheritdoc}
     */
    public function getLogDir(): string
    {
        return $this->getDir(defined('LOG_DIR') ? LOG_DIR : $this->getContentDir() . '/log', $this->namespace);
    }

    /**
     * @throws Exception
     */
    private function getDir(string $prefix, string $namespace): string
    {
        $dir = sprintf('%s/%s/%s', rtrim($prefix, '/'), $this->environment, trim($namespace, '/'));
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception(sprintf('Can not create directory %s.', $dir));
            }
        }
        return $dir;
    }

    /**
     * Gets the path to the configuration directory.
     */
    public function getConfigDir(): string
    {
        return $this->getProjectDir() . '/config';
    }

    /**
     * Gets the path to the bundles configuration file.
     */
    private function getBundlesPath(): string
    {
        return $this->getConfigDir() . '/bundles.php';
    }

    public function getContentDir()
    {
        return defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : realpath(__DIR__ . '/../../../..');
    }

    /**
     * @inheritDoc
     */
    public function registerBundles()
    {
        $contents = require $this->getBundlesPath();
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) use ($loader) {
            $container->addObjectResource($this);
            $container->fileExists($this->getBundlesPath());
            $loader->import($this->getConfigDir() . '/services.yaml');
        });
    }

    public static function setEnv(string $env, string $value)
    {
        putenv(sprintf('%s=%s', $env, $value));
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }
}