<?php

namespace Rdlv\WordPress\Sywo\Twig;

use Psr\EventDispatcher\EventDispatcherInterface;
use Rdlv\WordPress\Sywo\Event\RenderEvent;

class Environment extends \Twig\Environment
{
    private EventDispatcherInterface $dispatcher;

    public function render($name, array $context = []): string
    {
        $event = new RenderEvent($name, $context);
        $this->dispatcher->dispatch($event);
        return parent::render($event->name, $event->context);
    }

    public function display($name, array $context = []): void
    {
        $event = new RenderEvent($name, $context);
        $this->dispatcher->dispatch($event);
        parent::display($event->name, $event->context);
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     * @required
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }
}