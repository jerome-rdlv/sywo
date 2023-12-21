<?php

namespace Rdlv\WordPress\Sywo\EventListener;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener as BaseWebDebugToolbarListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class WebDebugToolbarListener implements EventSubscriberInterface, ServiceSubscriberInterface
{
    private ContainerInterface $locator;
    private ?BaseWebDebugToolbarListener $wdt;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public function setWdt(?BaseWebDebugToolbarListener $wdt): void
    {
        $this->wdt = $wdt;
    }

    public function response(ResponseEvent $event): void
    {
        if (!$this->wdt) {
            return;
        }

        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->locator->has('http_kernel')) {
            return;
        }

        $response = $event->getResponse();
        $content = $response->getContent();

        if (strripos($content, '</body>') !== false) {
            // this is a complete page, no need to manipulate toolbar
            return;
        }

        try {
            $kernel = $this->locator->get('http_kernel');
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface) {
            return;
        }

        // response with no <body>, retrieve the toolbar and insert it in page
        $clone = new Response('</body>', $response->getStatusCode(), $response->headers->all());

        $responseEvent = new ResponseEvent($kernel, $event->getRequest(), $event->getRequestType(), $clone);
        $this->wdt->onKernelResponse($responseEvent);

        $content = $responseEvent->getResponse()->getContent();
        $content = substr($content, 0, stripos($content, '</body>'));

        add_action('wp_footer', function () use ($content) {
            echo $content;
        });
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['response', -128],
        ];
    }

    public static function getSubscribedServices(): array
    {
        return [
            'http_kernel' => '?'.HttpKernelInterface::class,
        ];
    }
}