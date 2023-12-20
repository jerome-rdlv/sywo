<?php

namespace Rdlv\WordPress\Sywo\EventListener;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class WebDebugToolbarListener implements EventSubscriberInterface, ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    private $locator;

    /** @var \Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener */
    private $original;

    /**
     * @param \Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener $original
     */
    public function setOriginal(\Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener $original): void
    {
        $this->original = $original;
    }

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public function response(ResponseEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->locator->has('http_kernel')) {
            return;
        }

        $response = $event->getResponse();
        $content = $response->getContent();

        if (strripos($content, '</body>') !== false) {
            return;
        }

        // response with no <body>, retreive the toolbar and insert it in page
        $clone = new Response('</body>', $response->getStatusCode(), $response->headers->all());

//        $wdt = $this->locator->get('web_profiler.debug_toolbar');
        $wdt = $this->original;
        $kernel = $this->locator->get('http_kernel');

        $responseEvent = new ResponseEvent($kernel, $event->getRequest(), $event->getRequestType(), $clone);
        $wdt->onKernelResponse($responseEvent);

        $content = $responseEvent->getResponse()->getContent();

        $content = substr($content, 0, stripos($content, '</body>'));

        add_action('wp_footer', function () use ($content) {
            echo $content;
        });
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['response', -128],
        ];
    }

    public static function getSubscribedServices()
    {
        return [
//            'web_profiler.debug_toolbar' => WebDebugToolbarListener::class,
            'http_kernel' => '?' . HttpKernelInterface::class,
        ];
    }
}