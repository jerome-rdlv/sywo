<?php

namespace Rdlv\WordPress\Sywo\EventListener;

use Rdlv\WordPress\Sywo\Event\RenderEvent;
use Rdlv\WordPress\Sywo\Hooks;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RenderEventSubscriber implements EventSubscriberInterface
{
    /** @var Hooks */
    private $hooks;

    public function __construct(Hooks $hooks)
    {
        $this->hooks = $hooks;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            RenderEvent::class => 'filter',
        ];
    }

    public function filter(RenderEvent $event)
    {
        $event->name = $this->hooks->filter('twig/render/template', $event->name, $event->context);
        $event->context = $this->hooks->filter('twig/render/context', $event->context, $event->name);
    }
}