<?php

namespace Rdlv\WordPress\Sywo\EventListener;

use Rdlv\WordPress\Sywo\Event\RenderEvent;
use Rdlv\WordPress\Sywo\Hooks;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RenderEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private Hooks $hooks)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RenderEvent::class => 'filter',
        ];
    }

    public function filter(RenderEvent $event): void
    {
        $event->name = $this->hooks->filter('twig/render/template', $event->name, $event->context);
        $event->context = $this->hooks->filter('twig/render/context', $event->context, $event->name);
    }
}