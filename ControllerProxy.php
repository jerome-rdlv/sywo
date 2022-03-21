<?php

namespace Rdlv\WordPress\Sywo;

use ReflectionMethod;
use Throwable;

class ControllerProxy
{
    /** @var Kernel */
    private $kernel;

    /** @var string */
    private $service;

    /** @var string */
    private $method;

    /** @var array */
    private $arguments = [];

    /** @var callable */
    private $catch;

    /** @var callable */
    private $configure;

    public function __construct(Kernel $kernel, string $service, string $method)
    {
        $this->kernel = $kernel;
        $this->service = $service;
        $this->method = $method;
    }

    public function __invoke(...$arguments)
    {
        try {
            $service = $this->kernel->getService($this->service);
            if (is_callable($this->configure)) {
                call_user_func($this->configure, $service, $this->method);
            }
            if ($this->arguments) {
                foreach ((new ReflectionMethod($service, $this->method))->getParameters() as $index => $parameter) {
                    if (array_key_exists($parameter->name, $this->arguments)) {
                        $arguments[$index] = $this->arguments[$parameter->name];
                    }
                }
            }
            return $this->kernel->call($this->service, $this->method, $arguments);
        } catch (Throwable $e) {
            if (is_callable($this->catch)) {
                return call_user_func($this->catch, $e, $this);
            }
            throw $e;
        }
    }

    public function arguments(array $arguments): self
    {
        $this->arguments = $arguments;
        return $this;
    }

    public function catch(callable $catch = null): self
    {
        $this->catch = $catch ?: function (Throwable $e) {
            echo $e->getMessage();
        };
        return $this;
    }

    public function configure(callable $configure): self
    {
        $this->configure = $configure;
        return $this;
    }
}